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
namespace Elabftw\Elabftw;

use \DateTime;
use \Exception;
use \Elabftw\Elabftw\Update;
use Defuse\Crypto\Crypto as Crypto;
use Defuse\Crypto\Key as Key;

/**
 * Timestamp an experiment with RFC 3161
 * Based on:
 * http://www.d-mueller.de/blog/dealing-with-trusted-timestamps-in-php-rfc-3161
 */
class TrustedTimestamps extends Entity
{
    /** instance of Config*/
    private $Config;

    /** array with config of the team */
    private $teamConfigArr;

    /** our database connection */
    protected $pdo;

    /** instance of Entity */
    public $Entity;

    /** ELAB_ROOT . uploads/ . $pdfFileName */
    private $pdfPath;
    /** elabid-timestamped.pdf */
    private $pdfRealName;
    /** a hash */
    private $pdfLongName;

    /** config (url, login, password, cert) */
    private $stampParams = array();
    /** things that get deleted with destruct method */
    private $tmpfiles = array();

    /** where we store the request file */
    private $requestfilePath;
    /** where we store the asn1 token */
    private $responsefilePath;

    /** our answer from TSA */
    private $binaryResponseString;
    /** the time of the timestamp */
    private $responseTime;

    /** hash algo for file */
    private $hashAlgorithm = 'sha256';

    /**
     * Give me an experiment id and a db and I make good pdf for you
     *
     * @param Config $config
     * @param Teams $teams
     * @param Entity $entity
     */
    public function __construct(Config $config, Teams $teams, Entity $entity)
    {
        $this->Config = $config;
        $this->Entity = $entity;
        $this->teamConfigArr = $teams->read();

        $this->pdo = Db::getConnection();

        // will be used in sqlUpdate()
        $this->setId($this->Entity->id);
        $this->canOrExplode('write');

        $this->generatePdf();

        // initialize with info from config
        $this->stampParams = $this->getTimestampParameters();
    }

    /**
     * Delete all temporary files created by TrustedTimestamps
     *
     */
    public function __destruct()
    {
        foreach ($this->tmpfiles as $file) {
            unlink($file);
        }
    }

    /**
     * Generate the pdf to timestamp.
     *
     * @throws Exception if it cannot make the pdf
     */
    private function generatePdf()
    {
        try {
            $pdf = new MakePdf($this->Entity, true);
            $this->pdfPath = $pdf->filePath;
            $this->pdfLongName = $pdf->fileName;
        } catch (Exception $e) {
            throw new Exception('Failed at making the pdf : ' . $e->getMessage());
        }
    }

    /**
     * Return the needed parameters to request/verify a timestamp
     *
     * @return array<string,string>
     */
    public function getTimestampParameters()
    {
        $hash_algorithms = array('sha256', 'sha384', 'sha512');

        // if there is a config in the team, use that
        // otherwise use the general config if we can
        if (strlen($this->teamConfigArr['stampprovider']) > 2) {
            $config = $this->teamConfigArr;
        } elseif ($this->Config->configArr['stampshare']) {
            $config = $this->Config->configArr;
        } else {
            throw new Exception(_('Please configure Timestamping in the admin panel.'));
        }

        $login = $config['stamplogin'];


        if (strlen($config['stamppass']) > 0) {
            $password = Crypto::decrypt($config['stamppass'], Key::loadFromAsciiSafeString(SECRET_KEY));
        } else {
            $password = '';
        }
        $provider = $config['stampprovider'];
        $cert = $config['stampcert'];
        $hash = $config['stamphash'];
        if (!in_array($hash, $hash_algorithms)) {
            $hash = 'sha256';
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
    private function runOpenSSL($cmd)
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
    private function runSh($cmd)
    {
        $retarray = array();
        exec("sh -c \"" . $cmd . "\" 2>&1", $retarray, $retcode);

        return array(
            "retarray" => $retarray,
            "retcode" => $retcode
        );
    }

    /**
     * Create a tempfile in uploads/tmp temp path
     *
     * @param string $str Content which should be written to the newly created tempfile
     * @return string filepath of the created tempfile
     */
    private function createTempFile($str = "")
    {
        $tempfilename = tempnam(ELAB_ROOT . 'uploads/tmp', rand());

        if (!file_exists($tempfilename)) {
                    throw new Exception("Tempfile could not be created");
        }

        if (!empty($str) && !file_put_contents($tempfilename, $str)) {
                    throw new Exception("Could not write to tempfile");
        }

        array_push($this->tmpfiles, $tempfilename);

        return $tempfilename;
    }

    /**
     * Creates a Timestamp Requestfile from a filename
     *
     * @throws Exception
     */
    private function createRequestfile()
    {
        if (!is_readable($this->pdfPath)) {
            throw new Exception('Pdf not found! This is a bug!');
        }
        $outfilepath = $this->createTempFile();
        $cmd = "ts -query -data " . escapeshellarg($this->pdfPath) . " -cert -" . $this->stampParams['hash'] . " -no_nonce -out " . escapeshellarg($outfilepath);
        $opensslResult = $this->runOpenSSL($cmd);
        $retarray = $opensslResult['retarray'];
        $retcode = $opensslResult['retcode'];

        if ($retcode !== 0) {
            throw new Exception("OpenSSL does not seem to be installed: " . implode(", ", $retarray));
        }

        if (stripos($retarray[0], "openssl:Error") !== false) {
            throw new Exception("There was an error with OpenSSL. Is version >= 0.99 installed?: " . implode(", ", $retarray));
        }

        $this->requestfilePath = $outfilepath;
    }

    /**
     * Extracts the unix timestamp from the base64-encoded response string as returned by signRequestfile
     *
     * @throws Exception if unhappy
     */
    private function setResponseTime()
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
     */
    private function postData()
    {
        $ch = curl_init();
        // set url of TSA
        curl_setopt($ch, CURLOPT_URL, $this->stampParams['stampprovider']);
        // if login and password are set, pass them to curl
        if ($this->stampParams['stamplogin'] && $this->stampParams['stamppassword']) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->stampParams['stamplogin'] . ":" . $this->stampParams['stamppassword']);
        }
        // add proxy if there is one
        if (strlen($this->Config->configArr['proxy']) > 0) {
            curl_setopt($ch, CURLOPT_PROXY, $this->Config->configArr['proxy']);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($this->requestfilePath));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/timestamp-query'));
        curl_setopt($ch, CURLOPT_USERAGENT, "Elabftw/" . Update::INSTALLED_VERSION);
        $binaryResponseString = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status != 200 || !strlen($binaryResponseString)) {
            // check if we got something bad
            throw new Exception('Bad answer from TSA (' . $status . ')<br>' . $binaryResponseString);
        }

        // populate variable
        $this->binaryResponseString = $binaryResponseString;
    }

    /**
     * Get the hash of a file
     *
     * @param string $file Path to the file
     * @throws Exception if file is not readable
     * @return string Hash of the file
     */
    private function getHash($file)
    {
        if (!is_readable($file)) {
            throw new Exception('Not a file!');
        }
        return hash_file($this->hashAlgorithm, $file);
    }
    /**
     * Save the binaryResponseString to a .asn1 file (token)
     *
     */
    private function saveToken()
    {
        $long_name = hash("sha512", uniqid(rand(), true)) . ".asn1";
        $file_path = ELAB_ROOT . 'uploads/' . $long_name;
        if (!file_put_contents($file_path, $this->binaryResponseString)) {
            throw new Exception('Cannot save token to disk!');
        }
        $this->responsefilePath = $file_path;

        $real_name = $this->pdfRealName . '.asn1';

        // keep a trace of where we put the token
        $sql = "INSERT INTO uploads(real_name, long_name, comment, item_id, userid, type, hash, hash_algorithm)
            VALUES(:real_name, :long_name, :comment, :item_id, :userid, :type, :hash, :hash_algorithm)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':real_name', $real_name);
        $req->bindParam(':long_name', $long_name);
        $req->bindValue(':comment', "Timestamp token");
        $req->bindParam(':item_id', $this->Entity->id);
        $req->bindParam(':userid', $this->Entity->Users->userid);
        $req->bindValue(':type', 'timestamp-token');
        $req->bindParam(':hash', $this->getHash($this->responsefilePath));
        $req->bindParam(':hash_algorithm', $this->hashAlgorithm);
        if (!$req->execute()) {
            throw new Exception('Cannot insert into SQL!');
        }
    }

    /**
     * Validates a file against its timestamp and optionally check a provided time for consistence with the time encoded
     * in the timestamp itself.
     *
     * @return bool
     */
    private function validate()
    {
        $cmd = "ts -verify -data " . escapeshellarg($this->pdfPath) . " -in " . escapeshellarg($this->responsefilePath) . " -CAfile " . escapeshellarg(ELAB_ROOT . $this->stampParams['stampcert']);

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

        if ($retcode === 0 && strtolower(trim($retarray[0])) == "verification: ok") {
            return true;
        }

        if (!is_array($retarray)) {
            throw new Exception('$retarray must be an array.');
        }

        foreach ($retarray as $retline) {
            if (stripos($retline, "message imprint mismatch") !== false) {
                            return false;
            }
            if (stripos($retline, "TS_CHECK_SIGNING_CERTS")) {
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
    private function isJavaInstalled()
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
    private function validateWithJava()
    {
        if (!$this->isJavaInstalled()) {
            throw new Exception("Could not validate the timestamp due to a bug in OpenSSL library. See <a href='https://github.com/elabftw/elabftw/issues/242#issuecomment-212382182'>issue #242</a>. Tried to validate with failsafe method but Java is not installed.");
        }

        chdir("../../app/dfn-cert/timestampverifier/");
        $cmd = "./verify.sh " . $this->requestfilePath . " " . $this->responsefilePath;
        $javaRes = $this->runSh($cmd);
        if (stripos($javaRes['retarray'][0], 'matches')) {
            return true;
        }
        throw new Exception('Could not validate the timestamp with java failsafe method. Please report this bug on Github.');
    }

    /**
     * Update SQL
     *
     */
    private function sqlUpdateExperiment()
    {
        $sql = "UPDATE experiments SET
            locked = 1,
            lockedby = :userid,
            lockedwhen = :when,
            timestamped = 1,
            timestampedby = :userid,
            timestampedwhen = :when,
            timestamptoken = :longname
            WHERE id = :id;";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':when', $this->responseTime);
        // the date recorded in the db has to match the creation time of the timestamp token
        $req->bindParam(':longname', $this->responsefilePath);
        $req->bindParam(':userid', $this->Entity->Users->userid);
        $req->bindParam(':id', $this->Entity->id);
        if (!$req->execute()) {
            throw new Exception('Cannot update SQL!');
        }
    }

    /**
     * The realname is elabid-timestamped.pdf
     *
     */
    private function setPdfRealName()
    {
        $sql = "SELECT elabid FROM experiments WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->Entity->id);
        if (!$req->execute()) {
            throw new Exception('Cannot get elabid!');
        }
        $this->pdfRealName = $req->fetch(\PDO::FETCH_COLUMN) . "-timestamped.pdf";
    }

    /**
     * Add also our pdf to the attached files of the experiment, this way it is kept safely :)
     * I had this idea when realizing that if you comment an experiment, the hash won't be good anymore. Because the pdf will contain the new comments.
     * Keeping the pdf here is the best way to go, as this leaves room to leave comments.
     */
    private function sqlInsertPdf()
    {
        $sql = "INSERT INTO uploads(real_name, long_name, comment, item_id, userid, type, hash, hash_algorithm) VALUES(:real_name, :long_name, :comment, :item_id, :userid, :type, :hash, :hash_algorithm)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':real_name', $this->pdfRealName);
        $req->bindParam(':long_name', $this->pdfLongName);
        $req->bindValue(':comment', "Timestamped PDF");
        $req->bindParam(':item_id', $this->Entity->id);
        $req->bindParam(':userid', $this->Entity->Users->userid);
        $req->bindValue(':type', 'exp-pdf-timestamp');
        $req->bindParam(':hash', $this->getHash($this->pdfPath));
        $req->bindParam(':hash_algorithm', $this->hashAlgorithm);

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
    public function decodeAsn1($token)
    {
        $cmd = "asn1parse -inform DER -in " . escapeshellarg(ELAB_ROOT . "uploads/" . $token);

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

        $oidArr = explode(":", $retarray[81]);
        $oid = $oidArr[3];

        $hashArr = explode(":", $retarray[12]);
        $hash = $hashArr[3];

        $messageArr = explode(":", $retarray[17]);
        $message = $messageArr[3];

        $timestampArr = explode(":", $retarray[142]);
        // for some reason the DateTime::createFromFormat didn't work
        // so we do it manually
        $timestamp = rtrim($timestampArr[3], 'Z');
        $year = "20" . substr($timestamp, 0, 2);
        $month = substr($timestamp, 2, 2);
        $day = substr($timestamp, 4, 2);
        $hour = substr($timestamp, 6, 2);
        $minute = substr($timestamp, 8, 2);
        $second = substr($timestamp, 10, 2);

        $countryArr = explode(":", $retarray[31]);
        $country = $countryArr[3];

        $tsaArr = explode(":", $retarray[121]);
        $tsa = $tsaArr[3];

        $tsaArr = explode(":", $retarray[39]);
        $tsa .= ", " . $tsaArr[3];
        $tsaArr = explode(":", $retarray[43]);
        $tsa .= ", " . $tsaArr[3];

        $out .= "Status: " . $status;
        $out .= "<br>Version: " . $version;
        $out .= "<br>OID: " . $oid;
        $out .= "<br>Hash algorithm: " . $hash;
        $out .= "<br>Message data: 0x" . $message;
        $out .= "<br>Timestamp: " . $year . "-" . $month . "-" . $day . " at " . $hour . ":" . $minute . ":" . $second;

        $out .= "<br><br>TSA info:";
        $out .= "<br>TSA: " . $tsa;
        $out .= "<br>Country: " . $country;

        return $out;
    }

    /**
     * The main function.
     * Request a timestamp and parse the response.
     *
     * @return bool True upon timestamping success, throw Exceptions in your face if it fails
     */
    public function timeStamp()
    {
        // first we create the request file
        $this->createRequestfile();

        // make the request to the TSA
        $this->postData();

        // we need the name of the pdf (elabid-timestamped.pdf)
        // for saving the token correctly
        $this->setPdfRealName();

        // save the token to .asn1 file
        $this->saveToken();

        // set the responseTime
        $this->setResponseTime();

        // validate everything so we are sure it is OK
        $this->validate();

        // SQL
        $this->sqlUpdateExperiment();
        $this->sqlInsertPdf();

        return true;
    }
}
