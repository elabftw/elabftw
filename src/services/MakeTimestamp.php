<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @copyright 2015 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\TimestampResponseInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use function hash_file;
use PDO;
use const SECRET_KEY;

/**
 * Timestamp an experiment with RFC 3161
 * Based on:
 * http://www.d-mueller.de/blog/dealing-with-trusted-timestamps-in-php-rfc-3161
 */
class MakeTimestamp extends AbstractMake
{
    /** default hash algo for file */
    private const TS_HASH = 'sha256';

    public string $pdfPath = '';

    /** @var Experiments $Entity */
    protected $Entity;

    // name of the pdf (elabid-timestamped.pdf)
    private string $pdfRealName = '';

    // a random long string
    private string $pdfLongName = '';

    // config (url, login, password, cert)
    private array $stampParams = array();

    public function __construct(protected array $configArr, Experiments $entity)
    {
        parent::__construct($entity);
        $this->Entity->canOrExplode('write');

        // stampParams contains login/pass/cert/url/hash information
        $this->stampParams = $this->getTimestampParameters();

        // set the name of the pdf (elabid + -timestamped.pdf)
        $this->pdfRealName = $this->getFileName();
    }

    /**
     * The realname is $elabid-timestamped.pdf
     */
    public function getFileName(): string
    {
        return $this->Entity->entityData['elabid'] . '-timestamped.pdf';
    }

    public function saveTimestamp(TimestampResponseInterface $tsResponse): bool
    {
        // keep track of the asn1 toke ni the db
        $this->sqlInsertToken($tsResponse);

        // SQL
        $responseTime = $this->formatResponseTime($tsResponse->getTimestampFromResponseFile());
        $this->Entity->updateTimestamp($responseTime, $tsResponse->getTokenName());
        return $this->sqlInsertPdf();
    }

    /**
     * Return the needed parameters to request/verify a timestamp
     *
     * @return array<string,string>
     */
    public function getTimestampParameters(): array
    {
        $config = $this->configArr;

        $password = '';
        if (($config['ts_password'] ?? '') !== '') {
            $password = Crypto::decrypt($config['ts_password'], Key::loadFromAsciiSafeString(SECRET_KEY));
        }

        $hash = $config['ts_hash'];
        $allowedAlgos = array('sha256', 'sha384', 'sha512');
        if (!in_array($hash, $allowedAlgos, true)) {
            $hash = self::TS_HASH;
        }

        return array(
            'ts_login' => $config['ts_login'],
            'ts_password' => $password,
            'ts_url' => $config['ts_url'],
            'ts_cert' => $config['ts_cert'],
            'ts_hash' => $hash,
            'ts_chain' => '/etc/ssl/cert.pem',
            );
    }

    /**
     * Generate the pdf to timestamp
     */
    public function generatePdf(): string
    {
        if (!$this->Entity->isTimestampable()) {
            throw new ImproperActionException('Timestamping is not allowed for this experiment.');
        }
        $userData = $this->Entity->Users->userData;
        $MpdfProvider = new MpdfProvider(
            $userData['fullname'],
            $userData['pdf_format'],
            (bool) $userData['pdfa'],
        );
        $MakePdf = new MakePdf($MpdfProvider, $this->Entity);
        $MakePdf->outputToFile();
        $this->pdfPath = $MakePdf->filePath;
        $this->pdfLongName = $MakePdf->longName;
        return $this->pdfPath;
    }

    /**
     * Convert the time found in the response file to the correct format for sql insertion
     */
    protected function formatResponseTime(string $timestamp): string
    {
        $time = strtotime($timestamp);
        if ($time === false) {
            throw new ImproperActionException('Could not get response time!');
        }
        return date('Y-m-d H:i:s', $time);
    }

    /**
     * Get the hash of a file
     *
     * @param string $file Path to the file
     * @throws ImproperActionException if file is not readable
     * @return string Hash of the file
     */
    private function getHash($file): string
    {
        $hash = hash_file($this->stampParams['ts_hash'], $file);
        if ($hash === false) {
            throw new ImproperActionException('The file is not readable.');
        }
        return $hash;
    }

    /**
     * Save the binaryToken to a .asn1 file
     */
    private function sqlInsertToken(TimestampResponseInterface $tsResponse): bool
    {
        $realName = $this->pdfRealName . '.asn1';
        $hash = $this->getHash($tsResponse->getTokenPath());

        // keep a trace of where we put the token
        $sql = 'INSERT INTO uploads(real_name, long_name, comment, item_id, userid, type, hash, hash_algorithm)
            VALUES(:real_name, :long_name, :comment, :item_id, :userid, :type, :hash, :hash_algorithm)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':real_name', $realName);
        $req->bindValue(':long_name', $tsResponse->getTokenName());
        $req->bindValue(':comment', 'Timestamp token');
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':type', 'timestamp-token');
        $req->bindParam(':hash', $hash);
        $req->bindParam(':hash_algorithm', $this->stampParams['ts_hash']);
        return $this->Db->execute($req);
    }

    /**
     * Add also our pdf to the attached files of the experiment, this way it is kept safely :)
     * I had this idea when realizing that if you comment an experiment, the hash won't be good anymore. Because the pdf will contain the new comments.
     * Keeping the pdf here is the best way to go, as this leaves room to leave comments.
     */
    private function sqlInsertPdf(): bool
    {
        $hash = $this->getHash($this->pdfPath);

        $sql = 'INSERT INTO uploads(real_name, long_name, comment, item_id, userid, type, hash, hash_algorithm) VALUES(:real_name, :long_name, :comment, :item_id, :userid, :type, :hash, :hash_algorithm)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':real_name', $this->pdfRealName);
        $req->bindParam(':long_name', $this->pdfLongName);
        $req->bindValue(':comment', 'Timestamped PDF');
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':type', 'exp-pdf-timestamp');
        $req->bindParam(':hash', $hash);
        $req->bindParam(':hash_algorithm', $this->stampParams['hash']);

        return $this->Db->execute($req);
    }
}
