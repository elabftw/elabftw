<?php
/**
 * \Elabftw\Elabftw\TrustedTimestamps
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @author David Müller
 * @copyright 2015 Nicolas CARPi, Alexander Minges
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use DateTime;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Exception;
use GuzzleHttp\Exception\RequestException;
use PDO;

/**
 * Timestamp an experiment with RFC 3161
 * Based on:
 * http://www.d-mueller.de/blog/dealing-with-trusted-timestamps-in-php-rfc-3161
 */
class TrustedTimestamps extends AbstractMake
{
    /** default hash algo for file */
    private const HASH_ALGORITHM = 'sha256';

    /** @var Config $Config instance of Config*/
    private $Config;

    /** @var array $teamConfigArr array with config of the team */
    private $teamConfigArr;

    /** @var string $pdfPath full path to pdf (ELAB_ROOT . uploads/ . $pdfFileName) */
    private $pdfPath;

    /** @var string $pdfRealName name of the pdf (elabid-timestamped.pdf) */
    private $pdfRealName;

    /** @var string $pdfLongName a hash */
    private $pdfLongName;

    /** @var array $stampParams config (url, login, password, cert) */
    private $stampParams;

    /** @var array $trash things that get deleted with destruct method */
    private $trash = array();

    /** @var string $requestfilePath where we store the request file */
    private $requestfilePath;

    /** @var string $responsefilePath where we store the asn1 token */
    private $responsefilePath;

    /** @var string $responseTime the time of the timestamp */
    private $responseTime;

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

        $this->Config = $config;
        $this->teamConfigArr = $teams->read();

        // initialize with info from config
        $this->stampParams = $this->getTimestampParameters();

        /** set the name of the pdf (elabid + -timestamped.pdf) */
        $this->pdfRealName = $this->getCleanName();
        $this->generatePdf();
    }

    /**
     * Generate the pdf to timestamp.
     *
     * @throws Exception if it cannot make the pdf
     * @return void
     */
    private function generatePdf(): void
    {
        try {
            $MakePdf = new MakePdf($this->Entity);
            $MakePdf->output(true, true);
            $this->pdfPath = $MakePdf->filePath;
            $this->pdfLongName = $MakePdf->fileName;
        } catch (Exception $e) {
            throw new Exception('Failed at making the pdf : ' . $e->getMessage());
        }
    }

    /**
     * Return the needed parameters to request/verify a timestamp
     *
     * @return array<string,string>
     */
    private function getTimestampParameters(): array
    {
        // if there is a config in the team, use that
        // otherwise use the general config if we can
        if (\mb_strlen($this->teamConfigArr['stampprovider']) > 2) {
            $config = $this->teamConfigArr;
        } elseif ($this->Config->configArr['stampshare']) {
            $config = $this->Config->configArr;
        } else {
            throw new Exception(_('Please configure Timestamping in the admin panel.'));
        }

        $login = $config['stamplogin'];


        if (\mb_strlen($config['stamppass']) > 0) {
            $password = Crypto::decrypt($config['stamppass'], Key::loadFromAsciiSafeString(SECRET_KEY));
        } else {
            $password = '';
        }
        $provider = $config['stampprovider'];
        $cert = $config['stampcert'];
        $hash = $config['stamphash'];

        $allowedAlgos = array('sha256', 'sha384', 'sha512');
        if (!in_array($hash, $allowedAlgos)) {
            $hash = self::HASH_ALGORITHM;
        }

        return array('stamplogin' => $login,
                    'stamppassword' => $password,
                    'stampprovider' => $provider,
                    'stampcert' => $cert,
                    'hash' => $hash);
    }

    /**
     * Run OpenSSL via exec() with a provided command
     * @param string $cmd
     * @return array<string,null|array|integer>
     */
    private function runOpenSSL($cmd): array
    {
        $retarray = array();
        exec("openssl " . $cmd . " 2>&1", $retarray, $retcode);

        return array(
            "retarray" => $retarray,
            "retcode" => $retcode
        );
    }

    /**
     * Run a shell command
     *
     * @param string $cmd
     * @return array<string,null|array|integer>
     */
    private function runSh($cmd): array
    {
        $retarray = array();
        exec("sh -c \"" . $cmd . "\" 2>&1", $retarray, $retcode);

        return array(
            "retarray" => $retarray,
            "retcode" => $retcode
        );
    }

    /**
     * Creates a Timestamp Requestfile from a filename
     *
     * @throws Exception
     * @return void
     */
    private function createRequestfile(): void
    {
        $this->requestfilePath = $this->getTmpPath() . $this->getUniqueString();
        // we don't keep this file around
        $this->trash[] = $this->requestfilePath;

        $cmd = "ts -query -data " . escapeshellarg($this->pdfPath) . " -cert -" .
            $this->stampParams['hash'] . " -no_nonce -out " . escapeshellarg($this->requestfilePath);
        $opensslResult = $this->runOpenSSL($cmd);
        $retarray = $opensslResult['retarray'];
        $retcode = $opensslResult['retcode'];

        if ($retcode !== 0) {
            throw new Exception("OpenSSL does not seem to be installed: " . implode(", ", $retarray));
        }

        if ($retarray[0] && stripos($retarray[0], "openssl:Error") !== false) {
            throw new Exception(
                "There was an error with OpenSSL. Is version >= 0.99 installed?: " . implode(", ", $retarray)
            );
        }
    }

    /**
     * Extracts the unix timestamp from the base64-encoded response string as returned by signRequestfile
     *
     * @throws Exception if unhappy
     * @return void
     */
    private function setResponseTime(): void
    {
        if (!is_readable($this->responsefilePath)) {
            throw new Exception('Bad token');
        }

        $cmd = "ts -reply -in " . escapeshellarg($this->responsefilePath) . " -text";
        $opensslResult = $this->runOpenSSL($cmd);
        $retarray = $opensslResult['retarray'];
        $retcode = $opensslResult['retcode'];

        if ($retcode !== 0) {
            throw new Exception("The reply failed: " . implode(", ", $retarray));
        }

        if (!is_array($retarray)) {
            throw new Exception('$retarray must be an array.');
        }

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

        // loop each line to find the Time stamp line
        foreach ($retarray as $retline) {
            if (preg_match("~^Time\sstamp\:\s(.*)~", $retline, $matches)) {
                // try to automatically convert time to unique unix timestamp
                // and then convert it to proper format
                $this->responseTime = date("Y-m-d H:i:s", strtotime($matches[1]));

                // workaround for faulty php strtotime function, that does not handle times in format "Feb 25 23:29:13.331 2015 GMT"
                // currently this accounts for the format used presumably by Universign.eu
                if (!$this->responseTime) {
                    $date = DateTime::createFromFormat("M j H:i:s.u Y T", $matches[1]);
                    if ($date) {
                        // Return formatted time as this is what we will store in the database.
                        // PHP will take care of correct timezone conversions (if configured correctly)
                        $this->responseTime = date("Y-m-d H:i:s", $date->getTimestamp());
                    } else {
                        throw new Exception('Could not get response time!');
                    }
                }
                break;
            }
        }
    }

    /**
     * Contact the TSA and receive a token after successful timestamp
     *
     * @throws Exception
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function postData(): \Psr\Http\Message\ResponseInterface
    {
        $client = new \GuzzleHttp\Client();

        $options = array(
            // add user agent
            // http://developer.github.com/v3/#user-agent-required
            'headers' => [
                'User-Agent' => 'Elabftw/' . ReleaseCheck::INSTALLED_VERSION,
                'Content-Type' => 'application/timestamp-query',
                'Content-Transfer-Encoding' => 'base64'
            ],
            // add proxy if there is one
            'proxy' => $this->Config->configArr['proxy'],
            // add a timeout, because if you need proxy, but don't have it, it will mess up things
            // in seconds
            'timeout' => 5,
            'body' => file_get_contents($this->requestfilePath)
        );

        if ($this->stampParams['stamplogin'] && $this->stampParams['stamppassword']) {
            $options['auth'] = array(
                $this->stampParams['stamplogin'],
                $this->stampParams['stamppassword']
            );
        }

        try {
            return $client->request('POST', $this->stampParams['stampprovider'], $options);
        } catch (RequestException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Get the hash of a file
     *
     * @param string $file Path to the file
     * @throws Exception if file is not readable
     * @return string Hash of the file
     */
    private function getHash($file): string
    {
        if (!is_readable($file)) {
            throw new Exception('Not a file!');
        }
        return hash_file($this->stampParams['hash'], $file);
    }

    /**
     * Save the binaryToken to a .asn1 file
     *
     * @throws Exception
     * @param string $binaryToken asn1 response from TSA
     * @return void
     */
    private function saveToken($binaryToken): void
    {
        $longName = $this->getUniqueString() . ".asn1";
        $filePath = $this->getUploadsPath() . $longName;
        if (!file_put_contents($filePath, $binaryToken)) {
            throw new Exception('Cannot save token to disk!');
        }
        $this->responsefilePath = $filePath;

        $realName = $this->pdfRealName . '.asn1';
        $hash = $this->getHash($this->responsefilePath);

        // keep a trace of where we put the token
        $sql = "INSERT INTO uploads(real_name, long_name, comment, item_id, userid, type, hash, hash_algorithm)
            VALUES(:real_name, :long_name, :comment, :item_id, :userid, :type, :hash, :hash_algorithm)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':real_name', $realName);
        $req->bindParam(':long_name', $longName);
        $req->bindValue(':comment', "Timestamp token");
        $req->bindParam(':item_id', $this->Entity->id);
        $req->bindParam(':userid', $this->Entity->Users->userid);
        $req->bindValue(':type', 'timestamp-token');
        $req->bindParam(':hash', $hash);
        $req->bindParam(':hash_algorithm', $this->stampParams['hash']);
        if (!$req->execute()) {
            throw new Exception('Cannot insert into SQL!');
        }
    }

    /**
     * Validates a file against its timestamp and optionally check a provided time for consistence with the time encoded
     * in the timestamp itself.
     *
     * @throws Exception
     * @return bool
     */
    private function validate(): bool
    {
        $elabRoot = dirname(__DIR__, 2);
        $cmd = "ts -verify -data " . escapeshellarg($this->pdfPath) . " -in " . escapeshellarg($this->responsefilePath) . " -CAfile " . escapeshellarg($elabRoot . '/' . $this->stampParams['stampcert']);

        $opensslResult = $this->runOpenSSL($cmd);
        $retarray = $opensslResult['retarray'];
        $retcode = $opensslResult['retcode'];

        /*
         * just 2 "normal" cases:
         *  1) Everything okay -> retcode 0 + retarray[0] == "Verification: OK"
         *  2) Hash is wrong -> retcode 1 + strpos(retarray[somewhere], "message imprint mismatch") !== false
         *
         * every other case (Certificate not found / invalid / openssl is not installed / ts command not known)
         * are being handled the same way -> retcode 1 + any retarray NOT containing "message imprint mismatch"
         */
        if (!is_array($retarray)) {
            throw new Exception('$retarray must be an array.');
        }

        if ($retcode === 0 && (strtolower(trim($retarray[0])) === "verification: ok" ||
            strtolower(trim($retarray[1])) === "verification: ok")) {
            return true;
        }

        foreach ($retarray as $retline) {
            if (stripos($retline, "message imprint mismatch") !== false) {
                return false;
            }
            if (stripos($retline, "TS_CHECK_SIGNING_CERTS") || stripos($retline, "FAILED")) {
                // we are facing the OpenSSL bug discussed here:
                // https://github.com/elabftw/elabftw/issues/242#issuecomment-212382182
                return $this->validateWithJava();
            }
        }

        throw new Exception("System command failed: " . implode(", ", $retarray));
    }

    /**
     * Check if we have java
     *
     * @return bool
     */
    private function isJavaInstalled(): bool
    {
        $res = $this->runSh("java");
        return (bool) stripos($res['retarray'][0], 'class');
    }

    /**
     * Validate the timestamp with java and BouncyCastle lib
     * We need this because of the openssl bug
     *
     * @throws Exception
     * @return bool
     */
    private function validateWithJava(): bool
    {
        if (!$this->isJavaInstalled()) {
            throw new Exception("Could not validate the timestamp due to a bug in OpenSSL library. See <a href='https://github.com/elabftw/elabftw/issues/242#issuecomment-212382182'>issue #242</a>. Tried to validate with failsafe method but Java is not installed.");
        }

        $elabRoot = dirname(__DIR__, 2);
        chdir($elabRoot . '/src/dfn-cert/timestampverifier/');
        $cmd = "./verify.sh " . $this->requestfilePath . " " . $this->responsefilePath;
        $javaRes = $this->runSh($cmd);
        if (stripos($javaRes['retarray'][0], 'matches')) {
            return true;
        }
        $msg = 'Could not validate the timestamp with java failsafe method. Maybe your java version is too old? Please report this bug on GitHub. Error is: ';
        $msg .= $javaRes['retarray'][0];
        throw new Exception($msg);
    }

    /**
     * The realname is elabid-timestamped.pdf
     *
     * @throws Exception
     * @return string
     */
    public function getCleanName(): string
    {
        $sql = "SELECT elabid FROM experiments WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);
        if (!$req->execute()) {
            throw new Exception('Cannot get elabid!');
        }
        return $req->fetch(PDO::FETCH_COLUMN) . "-timestamped.pdf";
    }

    /**
     * Add also our pdf to the attached files of the experiment, this way it is kept safely :)
     * I had this idea when realizing that if you comment an experiment, the hash won't be good anymore. Because the pdf will contain the new comments.
     * Keeping the pdf here is the best way to go, as this leaves room to leave comments.
     * @throws Exception
     * @return void
     */
    private function sqlInsertPdf(): void
    {
        $hash = $this->getHash($this->pdfPath);

        $sql = "INSERT INTO uploads(real_name, long_name, comment, item_id, userid, type, hash, hash_algorithm) VALUES(:real_name, :long_name, :comment, :item_id, :userid, :type, :hash, :hash_algorithm)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':real_name', $this->pdfRealName);
        $req->bindParam(':long_name', $this->pdfLongName);
        $req->bindValue(':comment', "Timestamped PDF");
        $req->bindParam(':item_id', $this->Entity->id);
        $req->bindParam(':userid', $this->Entity->Users->userid);
        $req->bindValue(':type', 'exp-pdf-timestamp');
        $req->bindParam(':hash', $hash);
        $req->bindParam(':hash_algorithm', $this->stampParams['hash']);

        if (!$req->execute()) {
            throw new Exception('Cannot insert into SQL!');
        }
    }

    /**
     * Decode asn1 encoded token
     *
     * @param string $token
     * @return string
     */
    public function decodeAsn1($token): string
    {
        $elabRoot = dirname(__DIR__, 2);
        $cmd = "asn1parse -inform DER -in " . escapeshellarg($elabRoot . '/uploads/' . $token);

        $opensslResult = $this->runOpenSSL($cmd);
        $retarray = $opensslResult['retarray'];
        $retcode = $opensslResult['retcode'];

        if ($retcode !== 0) {
            throw new Exception("Error decoding ASN1 file: " . implode(", ", $retarray));
        }

        // now let's parse this
        $out = "<br><hr>";

        $statusArr = explode(":", $retarray[4]);
        $status = $statusArr[3];

        $versionArr = explode(":", $retarray[111]);
        $version = $versionArr[3];

        $oidArr = explode(":", $retarray[148]);
        $oid = $oidArr[3];

        $hashArr = explode(":", $retarray[12]);
        $hash = $hashArr[3];

        $messageArr = explode(":", $retarray[17]);
        $message = $messageArr[3];

        $utctimeArr = explode(":", $retarray[142]);
        $utctime = rtrim($utctimeArr[3], 'Z');
        $timestamp = \DateTime::createFromFormat('ymdHis', $utctime);

        $countryArr = explode(":", $retarray[31]);
        $country = $countryArr[3];

        $tsaArr = explode(":", $retarray[121]);
        $tsa = $tsaArr[3];

        $tsaArr = explode(":", $retarray[39]);
        $tsa .= ", " . $tsaArr[3];
        $tsaArr = explode(":", $retarray[43]);
        $tsa .= ", " . $tsaArr[3];

        $out .= "<strong>Status</strong>: " . $status;
        $out .= "<br>Version: " . $version;
        $out .= "<br>OID: " . $oid;
        $out .= "<br>Hash algorithm: " . $hash;
        $out .= "<br>Message data: 0x" . $message;
        $out .= "<br>Timestamp: " . $timestamp->format('Y-m-d H:i:s');

        $out .= "<br><br><strong>TSA info:</strong>";
        $out .= "<br>TSA: " . $tsa;
        $out .= "<br>Country: " . $country;

        return $out;
    }

    /**
     * The main function.
     * Request a timestamp and parse the response.
     *
     * @throws Exception
     * @return bool True upon timestamping success, throw Exceptions in your face if it fails
     */
    public function timeStamp(): bool
    {
        // first we create the request file
        $this->createRequestfile();

        // get an answer from the TSA and
        // save the token to .asn1 file
        $this->saveToken($this->postData()->getBody());

        // set the responseTime
        $this->setResponseTime();

        // validate everything so we are sure it is OK
        $this->validate();

        // SQL
        if ($this->Entity instanceof Experiments && !$this->Entity->updateTimestamp($this->responseTime, $this->responsefilePath)) {
            throw new Exception('Cannot update SQL!');
        }
        $this->sqlInsertPdf();

        return true;
    }

    /**
     * Delete all temporary files created by TrustedTimestamps
     *
     */
    public function __destruct()
    {
        foreach ($this->trash as $file) {
            unlink($file);
        }
    }
}
