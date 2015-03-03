<?php
namespace Elabftw\Elabftw;

class TrustedTimestamps {
    private $stampprovider;
    private $data;
    private $stamplogin;
    private $stamppassword;
    private $stampcert;
    private $requestfile_path;
    
    private $binary_response_string;
    private $base64_response_string;
    private $response_time;
    
    private $responsefile_path;

    public function __construct($stampprovider=NULL, $data=NULL, $responsefile_path = NULL, $stamplogin = NULL, $stamppassword = NULL, $stampcert = NULL) {
        $this->stampprovider = $stampprovider;
        $this->data = $data;
        $this->responsefile_path = $responsefile_path;
        $this->stamplogin = $stamplogin;
        $this->stamppassword = $stamppassword;
        $this->stampcert = $stampcert;
        
        if (!is_null($this->data) and !is_null($this->stampprovider)) {
            $this->createRequestfile();
            $this->generateToken();
        } 
        
        if (!is_null($responsefile_path)) {
            $this->processResponsefile();
        }
    }
    
    public function getBinaryResponse() {
        if(!is_null($this->binary_response_string)) {
            return $this->binary_response_string;
        } else {
            return False;
        }
    }
    
    public function getBase64Response() {
        if(!is_null($this->base64_response_string)) {
        return $this->base64_response_string;
        } else {
            return False;
        }
    }
    
    public function getResponseTime() {
        if(!is_null($this->response_time)) {
        return $this->response_time;
        } else {
            return False;
        }
    }
    
    /**
     * Create a tempfile in the systems temp path
     *
     * @param string $str: Content which should be written to the newly created tempfile
     * @return string: filepath of the created tempfile
     */
    private function createTempFile ($str = "") {
        $tempfilename = tempnam(sys_get_temp_dir(), rand());

        if (!file_exists($tempfilename))
            throw new \Exception("Tempfile could not be created");
            
        if (!empty($str) && !file_put_contents($tempfilename, $str))
            throw new \Exception("Could not write to tempfile");

        return $tempfilename;
    }
    
    private function processResponsefile() {
        if(is_file($this->responsefile_path)) {
            $this->binary_response_string = file_get_contents($this->responsefile_path);
            $this->base64_response_string = base64_encode($this->binary_response_string);
            $this->responsefile_path = $this->createTempFile($this->binary_response_string);
            $this->response_time = $this->getTimestampFromAnswer ($this->base64_response_string);
        } else {
            throw new \Exception("The responsefile " . $this->responsefile_path . " was not found!");
        }
    }
    
    /**
     * Creates a Timestamp Requestfile from a filename
     *
     * @param string $data: Filename to be hashed
     * @return string: path of the created timestamp-requestfile
     */
    private function createRequestfile () {
        $outfilepath = $this->createTempFile();
        $cmd = "openssl ts -query -data ".escapeshellarg($this->data)." -cert -sha256 -no_nonce -out ".escapeshellarg($outfilepath);
        $retarray = array();
        exec($cmd." 2>&1", $retarray, $retcode);
        
        if ($retcode !== 0)
            throw new \Exception("OpenSSL does not seem to be installed: ".implode(", ", $retarray));
        
        if (stripos($retarray[0], "openssl:Error") !== false)
            throw new \Exception("There was an error with OpenSSL. Is version >= 0.99 installed?: ".implode(", ", $retarray));

         $this->requestfile_path = $outfilepath;
    }
    
    /**
     * Extracts the unix timestamp from the base64-encoded response string as returned by signRequestfile
     *
     * @param string $base64_response_string: Response string as returned by signRequestfile
     * @return int: unix timestamp
     */
    private function getTimestampFromAnswer ($responsefile_path = NULL) {
        if (is_null($this->responsefile_path) and !is_null($this->binary_response_string)) {
            $this->responsefile_path = $this->createTempFile();
            file_put_contents($this->responsefile_path, $this->binary_response_string);
        }
        
        
        $cmd = "openssl ts -reply -in ".escapeshellarg($this->responsefile_path)." -text";
        
        $retarray = array();
        exec($cmd." 2>&1", $retarray, $retcode);
        
        if ($retcode !== 0)
            throw new \Exception("The reply failed: ".implode(", ", $retarray));
        
        $matches = array();
        $response_time = 0;

        /*
         * Format of answer:
         * 
         * Foobar: some stuff
         * Time stamp: 21.08.2010 blabla GMT
         * Somestuff: Yayayayaya
         */
        
        foreach ($retarray as $retline)
        {
            if (preg_match("~^Time\sstamp\:\s(.*)~", $retline, $matches))
            {
                // try to automatically convert time to unique unix timestamp
                $response_time = strtotime($matches[1]);
                // workaround for faulty php strtotime function, that does not handle times in format "Feb 25 23:29:13.331 2015 GMT"
                // currently this accounts for the format used presumably by Universign.eu
                if(!$response_time) {
                    $date = DateTime::createFromFormat("M j H:i:s.u Y T", $matches[1]);
                    if($date) {
                        $response_time = $date->getTimestamp();
                    } else {
                        $response_time = False;
                    }
                }
                break;      
            }
        }

        if (!$response_time)
            throw new \Exception("The Timestamp was not found"); 
        
        /* Return formatted time as this is, what we will store in the database.
         * PHP will take care of correct timezone conversions (if configured correctly)
         */
        return date("Y-m-d H:i:s", $response_time);
    }
    
    private function generateToken () {   
        if (is_null($this->requestfile_path)) {
            throw new \Exception("Cannot create new timestamp token! No data was provided during initialization!");
        } elseif (!file_exists($this->requestfile_path)) {
            throw new \Exception("The Requestfile was not found: ". $this->requestfile_path);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->stampprovider);
        // if login and password are set, pass them to curl
        if (!is_null($this->stamplogin) and !is_null($this->stamppassword)) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->stamplogin.":".$this->stamppassword);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($this->requestfile_path));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/timestamp-query'));
        //Be honest about our user agent instead of faking an ancienct IE
        curl_setopt($ch, CURLOPT_USERAGENT, "eLabFTW/1.1.0");
        //curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        $binary_response_string = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($status != 200 || !strlen($binary_response_string))
            // return false if request fails. Must be catched by calling function!
            return false;
        
        $base64_response_string = base64_encode($binary_response_string);
        
        $this->binary_response_string = $binary_response_string;
        $this->base64_response_string = $base64_response_string;
        $this->response_time = $this->getTimestampFromAnswer($base64_response_string);
    }
    
    /**
     *
     * @param string $filename: filename of the data which should be checked
     * @param string $base64_response_string: The response string as returned by signRequestfile
     * @param int $response_time: The response time, which should be checked
     * @param string $tsa_cert_file: The path to the TSAs certificate chain (e.g. https://pki.pca.dfn.de/global-services-ca/pub/cacert/chain.txt)
     * @return <type>
     */
    public function validate ($time_to_check = NULL)
    {       
        if (!is_file($this->responsefile_path))
            throw new \Exception("There was no response-string");    
            
        if (!intval($this->response_time))
            throw new \Exception("There is no valid response-time given");
            
        if (!file_exists($this->stampcert))
            throw new \Exception("The TSA-Certificate could not be found");

        $cmd = "openssl ts -verify -data ".escapeshellarg($this->data)." -in ".escapeshellarg($this->responsefile_path)." -CAfile ".escapeshellarg($this->stampcert);
        
        $retarray = array();
        exec($cmd." 2>&1", $retarray, $retcode);
        
        /*
         * just 2 "normal" cases: 
         *  1) Everything okay -> retcode 0 + retarray[0] == "Verification: OK"
         *  2) Hash is wrong -> retcode 1 + strpos(retarray[somewhere], "message imprint mismatch") !== false
         * 
         * every other case (Certificate not found / invalid / openssl is not installed / ts command not known)
         * are being handled the same way -> retcode 1 + any retarray NOT containing "message imprint mismatch"
         */
        
        if ($retcode === 0 && strtolower(trim($retarray[0])) == "verification: ok")
        {
        
            if (!is_null($time_to_check)) {
                if ($time_to_check != $this->response_time) {
                    throw new \Exception("The response time of the request was changed");
                }
            }
            return true;
        }

        foreach ($retarray as $retline)
        {
            if (stripos($retline, "message imprint mismatch") !== false)
                return false;
        }

        throw new \Exception("Systemcommand failed: ".implode(", ", $retarray));
    }
}


?>