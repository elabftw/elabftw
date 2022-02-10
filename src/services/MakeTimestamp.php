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

use ZipArchive;
use Elabftw\Elabftw\FsTools;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\CreateUpload;
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

    // config (url, login, password, cert)
    private array $stampParams = array();

    public function __construct(protected array $configArr, Experiments $entity)
    {
        parent::__construct($entity);
        $this->Entity->canOrExplode('write');

        // stampParams contains login/pass/cert/url/hash information
        $this->stampParams = $this->getTimestampParameters();
    }

    public function getFileName(): string
    {
        return 'TODO:remove';
    }

    public function saveTimestamp(TimestampResponseInterface $tsResponse): bool
    {
        // 20220210171842-timestamp.pdf
        $pdfName = date('YmdHis') . '-timestamp.pdf';
        $tokenName = str_replace('pdf', 'asn1', $pdfName);
        $zipName = str_replace('pdf', 'zip', $pdfName);

        // SQL
        $responseTime = $this->formatResponseTime($tsResponse->getTimestampFromResponseFile());
        $this->Entity->updateTimestamp($responseTime);

        // create a zip archive with the timestamped pdf and the asn1 token
        $zipPath = FsTools::getCacheFile() . '.zip';
        $ZipArchive = new ZipArchive();
        $ZipArchive->open($zipPath, ZipArchive::CREATE);
        $ZipArchive->addFile($tsResponse->getPdfPath(), $pdfName);
        $ZipArchive->addFile($tsResponse->getTokenPath(), $tokenName);
        $ZipArchive->close();
        $this->Entity->Uploads->create(new CreateUpload($zipName, $zipPath, _('Timestamp archive')));
        return true;
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
        return $MakePdf->getFileContent();
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
}
