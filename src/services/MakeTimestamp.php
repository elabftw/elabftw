<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @author David Müller
 * @copyright 2015 Nicolas CARPi, Alexander Minges
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use DateTime;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\ReleaseCheck;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Teams;
use GuzzleHttp\Exception\RequestException;
use function hash_file;
use function is_readable;
use function mb_strlen;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use PDO;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Timestamp an experiment with RFC 3161
 * Based on:
 * http://www.d-mueller.de/blog/dealing-with-trusted-timestamps-in-php-rfc-3161
 */
class MakeTimestamp extends AbstractMake
{
    /** default hash algo for file */
    private const HASH_ALGORITHM = 'sha256';

    /** @var Experiments $Entity instance of Experiments */
    protected $Entity;

    /** @var Config $Config instance of Config */
    private $Config;

    /** @var string $pdfPath full path to pdf */
    private $pdfPath = '';

    /** @var string $pdfRealName name of the pdf (elabid-timestamped.pdf) */
    private $pdfRealName = '';

    /** @var string $pdfLongName a hash */
    private $pdfLongName = '';

    /** @var array $stampParams config (url, login, password, cert) */
    private $stampParams = array();

    /** @var array $trash things that get deleted with destruct method */
    private $trash = array();

    /** @var string $requestfilePath where we store the request file */
    private $requestfilePath = '';

    /** @var string $responsefilePath where we store the asn1 token */
    private $responsefilePath = '';

    /**
     * Pdf is generated on instanciation and after you need to call timestamp()
     *
     * @param Config $config
     * @param Teams $teams
     * @param Experiments $entity
     */
    public function __construct(Config $config, Teams $teams, Experiments $entity)
    {
        parent::__construct($entity);
        $this->Entity->canOrExplode('write');

        $this->Config = $config;

        // initialize with info from config
        $this->stampParams = $this->getTimestampParameters($teams);

        /** set the name of the pdf (elabid + -timestamped.pdf) */
        $this->pdfRealName = $this->getFileName();
        $this->requestfilePath = $this->getTmpPath() . $this->getUniqueString();
        // we don't keep this file around
        $this->trash[] = $this->requestfilePath;
    }

    /**
     * Delete all temporary files
     *
     */
    public function __destruct()
    {
        foreach ($this->trash as $file) {
            unlink($file);
        }
    }

    /**
     * The realname is elabid-timestamped.pdf
     *
     * @throws ImproperActionException
     * @return string
     */
    public function getFileName(): string
    {
        $sql = 'SELECT elabid FROM experiments WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        if (!$req->execute()) {
            throw new ImproperActionException('Cannot get elabid!');
        }
        return $req->fetch(PDO::FETCH_COLUMN) . '-timestamped.pdf';
    }

    /**
     * Decode asn1 encoded token
     *
     * @param string $token
     * @return string
     */
    public function decodeAsn1($token): string
    {
        $output = $this->runProcess(array(
            'openssl',
            'asn1parse',
            '-inform',
            'DER',
            '-in',
            $this->getUploadsPath() . $token,
        ));
        $lines = explode("\n", $output);

        // now let's parse this
        $out = '<br><hr>';

        $statusArr = explode(':', $lines[4]);
        $status = $statusArr[3];

        $versionArr = explode(':', $lines[111]);
        $version = $versionArr[3];

        $oidArr = explode(':', $lines[148]);
        $oid = $oidArr[3];

        $hashArr = explode(':', $lines[12]);
        $hash = $hashArr[3];

        $messageArr = explode(':', $lines[17]);
        $message = $messageArr[3];

        $utctimeArr = explode(':', $lines[142]);
        $utctime = rtrim($utctimeArr[3], 'Z');
        $timestamp = \DateTime::createFromFormat('ymdHis', $utctime);
        if ($timestamp === false) {
            return 'Error: Could not parse timestamp!';
        }

        $countryArr = explode(':', $lines[31]);
        $country = $countryArr[3];

        $tsaArr = explode(':', $lines[121]);
        $tsa = $tsaArr[3];

        $tsaArr = explode(':', $lines[39]);
        $tsa .= ', ' . $tsaArr[3];
        $tsaArr = explode(':', $lines[43]);
        $tsa .= ', ' . $tsaArr[3];

        $out .= '<strong>Status</strong>: ' . $status;
        $out .= '<br>Version: ' . $version;
        $out .= '<br>OID: ' . $oid;
        $out .= '<br>Hash algorithm: ' . $hash;
        $out .= '<br>Message data: 0x' . $message;
        $out .= '<br>Timestamp: ' . $timestamp->format('Y-m-d H:i:s P');

        $out .= '<br><br><strong>TSA info:</strong>';
        $out .= '<br>TSA: ' . $tsa;
        $out .= '<br>Country: ' . $country;

        return $out;
    }

    /**
     * The main function.
     * Request a timestamp and parse the response.
     *
     * @throws ImproperActionException
     * @return void
     */
    public function timestamp(): void
    {
        if (!$this->Entity->isTimestampable()) {
            throw new ImproperActionException('Timestamping is not allowed for this experiment.');
        }

        // generate the pdf of the experiment that will be timestamped
        $this->generatePdf();

        // create the request file that will be sent to the TSA
        $this->createRequestfile();

        // get an answer from the TSA and
        // save the token to .asn1 file
        $this->saveToken($this->postData()->getBody());

        // validate everything so we are sure it is OK
        $this->validate();

        // SQL
        $this->Entity->updateTimestamp($this->getResponseTime(), $this->responsefilePath);
        $this->sqlInsertPdf();
    }

    /**
     * Generate the pdf to timestamp
     *
     * @return void
     */
    private function generatePdf(): void
    {
        $MakePdf = new MakePdf($this->Entity);
        $MakePdf->outputToFile();
        $this->pdfPath = $MakePdf->filePath;
        $this->pdfLongName = $MakePdf->longName;
    }

    /**
     * Return the needed parameters to request/verify a timestamp
     *
     * @param Teams $teams
     * @return array<string,string>
     */
    private function getTimestampParameters(Teams $teams): array
    {
        $teamConfigArr = $teams->read();
        // if there is a config in the team, use that
        // otherwise use the general config if we can
        if (mb_strlen($teamConfigArr['stampprovider'] ?? '') > 2) {
            $config = $teamConfigArr;
        } elseif ($this->Config->configArr['stampshare']) {
            $config = $this->Config->configArr;
        } else {
            throw new ImproperActionException(_('Please configure Timestamping in the admin panel.'));
        }

        $login = $config['stamplogin'];

        if (($config['stamppass'] ?? '') !== '') {
            $password = Crypto::decrypt($config['stamppass'], Key::loadFromAsciiSafeString(\SECRET_KEY));
        } else {
            $password = '';
        }
        $provider = $config['stampprovider'];
        $cert = $config['stampcert'];
        // fix for previous value of stampcert that was not updated when the cert was moved
        if ($cert === 'app/dfn-cert/pki.dfn.pem') {
            $cert = 'src/dfn-cert/pki.dfn.pem';
            $this->Config->update(array('stampcert' => $cert));
        }
        $hash = $config['stamphash'];

        $allowedAlgos = array('sha256', 'sha384', 'sha512');
        if (!in_array($hash, $allowedAlgos, true)) {
            $hash = self::HASH_ALGORITHM;
        }

        return array(
            'stamplogin' => $login,
            'stamppassword' => $password,
            'stampprovider' => $provider,
            'stampcert' => $cert,
            'hash' => $hash,
            );
    }

    /**
     * Run a process
     *
     * @param array<string> $args arguments including the executable
     * @param string|null $cwd command working directory
     * @return string
     */
    private function runProcess(array $args, ?string $cwd = null): string
    {
        $Process = new Process($args, $cwd);
        $Process->mustRun();

        return $Process->getOutput();
    }

    /**
     * Creates a Timestamp Requestfile from a filename
     *
     * @throws ImproperActionException
     * @return void
     */
    private function createRequestfile(): void
    {
        $this->runProcess(array(
            'openssl',
            'ts',
            '-query',
            '-data',
            $this->pdfPath,
            '-cert',
            '-' . $this->stampParams['hash'],
            '-no_nonce',
            '-out',
            $this->requestfilePath,
        ));
    }

    /**
     * Extracts the unix timestamp from the base64-encoded response string as returned by signRequestfile
     *
     * @throws ImproperActionException if unhappy
     * @return string
     */
    private function getResponseTime(): string
    {
        if (!is_readable($this->responsefilePath)) {
            throw new ImproperActionException('The token is not readable.');
        }

        $output = $this->runProcess(array(
            'openssl',
            'ts',
            '-reply',
            '-in',
            $this->responsefilePath,
            '-text',
        ));

        /*
         * Format of answer:
         *
         * Status info:
         *   Status: Granted.
         *   Status description: unspecified
         *   Failure info: unspecified
         *
         *   TST info:
         *   Version: 1
         *   Policy OID: 1.3.6.1.4.1.15819.5.2.2
         *   Hash Algorithm: sha256
         *   Message data:
         *       0000 - 3a 9a 6c 32 12 7f b0 c7-cd e0 c9 9e e2 66 be a9   :.l2.........f..
         *       0010 - 20 b9 b1 83 3d b1 7c 16-e4 ac b0 5f 43 bc 40 eb    ...=.|...._C.@.
         *   Serial number: 0xA7452417D851301981FA9A7CC2CF776B5934D3E5
         *   Time stamp: Apr 27 13:37:34.363 2015 GMT
         *   Accuracy: unspecified seconds, 0x01 millis, unspecified micros
         *   Ordering: yes
         *   Nonce: unspecified
         *   TSA: DirName:/CN=Universign Timestamping Unit 012/OU=0002 43912916400026/O=Cryptolog International/C=FR
         *   Extensions:
         */
        $matches = array();
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (preg_match("~^Time\sstamp\:\s(.*)~", $line, $matches)) {
                $responseTime = date('Y-m-d H:i:s', strtotime($matches[1]));
                // workaround for faulty php strtotime function, that does not handle times in format "Feb 25 23:29:13.331 2015 GMT"
                // currently this accounts for the format used presumably by Universign.eu
                if ($responseTime === '') {
                    $date = DateTime::createFromFormat('M j H:i:s.u Y T', $matches[1]);
                    if ($date instanceof DateTime) {
                        // Return formatted time as this is what we will store in the database.
                        // PHP will take care of correct timezone conversions (if configured correctly)
                        return date('Y-m-d H:i:s', $date->getTimestamp());
                    }
                } else {
                    return $responseTime;
                }
            }
        }
        throw new ImproperActionException('Could not get response time!');
    }

    /**
     * Contact the TSA and receive a token after successful timestamp
     *
     * @throws ImproperActionException
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function postData(): \Psr\Http\Message\ResponseInterface
    {
        $client = new \GuzzleHttp\Client();

        $options = array(
            // add user agent
            // http://developer.github.com/v3/#user-agent-required
            'headers' => array(
                'User-Agent' => 'Elabftw/' . ReleaseCheck::INSTALLED_VERSION,
                'Content-Type' => 'application/timestamp-query',
                'Content-Transfer-Encoding' => 'base64',
            ),
            // add proxy if there is one
            'proxy' => $this->Config->configArr['proxy'],
            // add a timeout, because if you need proxy, but don't have it, it will mess up things
            // in seconds
            'timeout' => 5,
            'body' => \file_get_contents($this->requestfilePath),
        );

        if ($this->stampParams['stamplogin'] && $this->stampParams['stamppassword']) {
            $options['auth'] = array(
                $this->stampParams['stamplogin'],
                $this->stampParams['stamppassword'],
            );
        }

        try {
            return $client->request('POST', $this->stampParams['stampprovider'], $options);
        } catch (RequestException $e) {
            throw new ImproperActionException($e->getMessage(), (int) $e->getCode(), $e);
        }
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
        $hash = hash_file($this->stampParams['hash'], $file);
        if ($hash === false) {
            throw new ImproperActionException('The file is not readable.');
        }
        return $hash;
    }

    /**
     * Save the binaryToken to a .asn1 file
     *
     * @throws ImproperActionException
     * @param StreamInterface $binaryToken asn1 response from TSA
     * @return void
     */
    private function saveToken(StreamInterface $binaryToken): void
    {
        $longName = $this->getLongName() . '.asn1';
        $filePath = $this->getUploadsPath() . $longName;
        $dir = \dirname($filePath);
        if (!\is_dir($dir) && !\mkdir($dir, 0700, true) && !\is_dir($dir)) {
            throw new FilesystemErrorException('Cannot create folder! Check permissions of uploads folder.');
        }
        if (!file_put_contents($filePath, $binaryToken)) {
            throw new FilesystemErrorException('Cannot save token to disk!');
        }
        $this->responsefilePath = $filePath;

        $realName = $this->pdfRealName . '.asn1';
        $hash = $this->getHash($this->responsefilePath);

        // keep a trace of where we put the token
        $sql = 'INSERT INTO uploads(real_name, long_name, comment, item_id, userid, type, hash, hash_algorithm)
            VALUES(:real_name, :long_name, :comment, :item_id, :userid, :type, :hash, :hash_algorithm)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':real_name', $realName);
        $req->bindParam(':long_name', $longName);
        $req->bindValue(':comment', 'Timestamp token');
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':type', 'timestamp-token');
        $req->bindParam(':hash', $hash);
        $req->bindParam(':hash_algorithm', $this->stampParams['hash']);
        if (!$req->execute()) {
            throw new ImproperActionException('Cannot insert into SQL!');
        }
    }

    /**
     * Validates a file against its timestamp and optionally check a provided time for consistence with the time encoded
     * in the timestamp itself.
     *
     * @throws ImproperActionException
     * @return bool
     */
    private function validate(): bool
    {
        $certPath = \dirname(__DIR__, 2) . '/' . $this->stampParams['stampcert'];

        if (!is_readable($certPath)) {
            throw new ImproperActionException('Cannot read the certificate file!');
        }

        try {
            $this->runProcess(array(
                'openssl',
                'ts',
                '-verify',
                '-data',
                $this->pdfPath,
                '-in',
                $this->responsefilePath,
                '-CAfile',
                $certPath,
            ));
        } catch (ProcessFailedException $e) {
            // we are facing the OpenSSL bug discussed here:
            // https://github.com/elabftw/elabftw/issues/242#issuecomment-212382182
            return $this->validateWithJava();
        }

        return true;
    }

    /**
     * Check if we have java
     *
     * @return void
     */
    private function isJavaInstalled(): void
    {
        try {
            $this->runProcess(array('which', 'java'));
        } catch (ProcessFailedException $e) {
            throw new ImproperActionException("Could not validate the timestamp due to a bug in OpenSSL library. See <a href='https://github.com/elabftw/elabftw/issues/242#issuecomment-212382182'>issue #242</a>. Tried to validate with failsafe method but Java is not installed.", (int) $e->getCode(), $e);
        }
    }

    /**
     * Validate the timestamp with java and BouncyCastle lib
     * We need this because of the openssl bug
     *
     * @throws ImproperActionException
     * @return bool
     */
    private function validateWithJava(): bool
    {
        $this->isJavaInstalled();

        $cwd = \dirname(__DIR__, 2) . '/src/dfn-cert/timestampverifier/';
        try {
            $output = $this->runProcess(array(
                './verify.sh',
                $this->requestfilePath,
                $this->responsefilePath,
            ), $cwd);
        } catch (ProcessFailedException $e) {
            $Log = new Logger('elabftw');
            $Log->pushHandler(new ErrorLogHandler());
            $Log->error('', array(array('userid' => $this->Entity->Users->userData['userid']), array('Error', $e)));
            $msg = 'Could not validate the timestamp with java failsafe method. Maybe your java version is too old? Please report this bug on GitHub.';
            throw new ImproperActionException($msg, (int) $e->getCode(), $e);
        }
        return (bool) stripos($output, 'matches');
    }

    /**
     * Add also our pdf to the attached files of the experiment, this way it is kept safely :)
     * I had this idea when realizing that if you comment an experiment, the hash won't be good anymore. Because the pdf will contain the new comments.
     * Keeping the pdf here is the best way to go, as this leaves room to leave comments.
     * @throws ImproperActionException
     * @return void
     */
    private function sqlInsertPdf(): void
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

        if (!$req->execute()) {
            throw new ImproperActionException('Cannot insert into SQL!');
        }
    }
}
