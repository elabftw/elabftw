<?php
/********************************************************************************
*                                                                               *
*  Copyright 2015 Alexander Minges (alexander.minges@gmail.com)                 *
*  http://www.elabftw.net/                                                      *
*                                                                               *
*  Based on work by David Müller:                                               *
*  http://www.d-mueller.de/blog/dealing-with-trusted-timestamps-in-php-rfc-3161 *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
*********************************************************************************/

namespace Elabftw\Elabftw;

class TrustedTimestamps {
    private $stampprovider;
    private $data;
    private $stamplogin;
    private $stamppassword;
    private $stampcert;
    private $requestfilePath;
    
    private $binaryResponseString;
    private $base64ResponseString;
    private $responseTime;
    
    private $responsefile_path;
    
    /**
     * Class constructor
     * At least $stampprovider + $data (+ $stamppassword + $stamplogin) or $data + $responsefile_path + $stampcert are needed
     * to do anything usefull.
     *
     * @param string $stampprovider URL of the TSA to be used (optional)
     * @param string $data Filename to be timestamped or validated (optional)
     * @param string $responsefile_path Filename to an already existing binary timestamp token (optional)
     * @param string $stamplogin Login for the TSA (optional)
     * @param string $stamppassword Password for the TSA (optional)
     * @param string $stampcert File with the certificate that is used by the TSA in PEM-encoded ASCII format (optional)
     */    
    public function __construct($stampProvider = NULL, $data = NULL, $responsefilePath = NULL, $stampLogin = NULL, 
                                $stampPassword = NULL, $stampCert = NULL) {
        $this->stampprovider = $stampProvider;
        $this->data = $data;
        $this->responsefile_path = $responsefilePath;
        $this->stamplogin = $stampLogin;
        $this->stamppassword = $stampPassword;
        $this->stampcert = $stampCert;
        $this->tmpfiles = [];
        
        if (!is_null($this->data) && !is_null($this->stampprovider)) {
            $this->createRequestfile();
            $this->generateToken();
        } 
        
        if (!is_null($responsefilePath)) {
            $this->processResponsefile();
        }
    }
    
    /**
     * Class destructor
     * Deletes all temporary files created by TrustedTimestamps
     */
    public function __destruct() {
        foreach ($this->tmpfiles as $file) {
            unlink($file);
        }
    }
    
    /**
    * Returns response in binary form
    * @return string|bool binary response or False on error
    */
    public function getBinaryResponse() {
        if(!is_null($this->binaryResponseString)) {
            return $this->binaryResponseString;
        } else {
            return False;
        }
    }

    /**
    * Returns response base64-encoded
    * @return string|bool base64-encoded response or False on error
    */
    public function getBase64Response() {
        if(!is_null($this->base64ResponseString)) {
        return $this->base64ResponseString;
        } else {
            return False;
        }
    }

    /**
    * Returns date and time of when the response was generated
    * @return string|bool response time or False on error
    */
    public function getResponseTime() {
        if(!is_null($this->responseTime)) {
        return $this->responseTime;
        } else {
            return False;
        }
    }
    
    /**
     * Create a tempfile in uploads/tmp temp path
     *
     * @param string $str Content which should be written to the newly created tempfile
     * @return string filepath of the created tempfile
     */
    private function createTempFile ($str = "") {
        $tempfilename = tempnam(ELAB_ROOT . 'uploads/tmp', rand());

        if (!file_exists($tempfilename))
            throw new \Exception("Tempfile could not be created");
            
        if (!empty($str) && !file_put_contents($tempfilename, $str))
            throw new \Exception("Could not write to tempfile");
            
        array_push($this->tmpfiles, $tempfilename);

        return $tempfilename;
    }

    /**
     * Process the response file and populate class variables accordingly.
     */
    private function processResponsefile() {
        if(is_file($this->responsefile_path)) {
            $this->binaryResponseString = file_get_contents($this->responsefile_path);
            $this->base64ResponseString = base64_encode($this->binaryResponseString);
            $this->responsefile_path = $this->createTempFile($this->binaryResponseString);
            $this->responseTime = $this->getTimestampFromAnswer ($this->base64ResponseString);
        } else {
            throw new \Exception("The responsefile " . $this->responsefile_path . " was not found!");
        }
    }
    
    /**
     * Creates a Timestamp Requestfile from a filename
     */
    private function createRequestfile () {
        $outfilepath = $this->createTempFile();
        $cmd = "ts -query -data ".escapeshellarg($this->data)." -cert -sha256 -no_nonce -out ".escapeshellarg($outfilepath);
        $opensslResult = $this->runOpenSSL($cmd);
        $retarray = $opensslResult['retarray'];
        $retcode = $opensslResult['retcode'];
        
        if ($retcode !== 0)
            throw new \Exception("OpenSSL does not seem to be installed: ".implode(", ", $retarray));
        
        if (stripos($retarray[0], "openssl:Error") !== false)
            throw new \Exception("There was an error with OpenSSL. Is version >= 0.99 installed?: ".implode(", ", $retarray));

         $this->requestfilePath = $outfilepath;
    }
    
    /**
     * Extracts the unix timestamp from the base64-encoded response string as returned by signRequestfile
     *
     * @return int unix timestamp
     */
    private function getTimestampFromAnswer () {
        if (is_null($this->responsefile_path) && !is_null($this->binaryResponseString)) {
            $this->responsefile_path = $this->createTempFile();
            file_put_contents($this->responsefile_path, $this->binaryResponseString);
        }
        
        
        $cmd = "ts -reply -in ".escapeshellarg($this->responsefile_path)." -text";
        $opensslResult = $this->runOpenSSL($cmd);
        $retarray = $opensslResult['retarray'];
        $retcode = $opensslResult['retcode'];
        
        if ($retcode !== 0)
            throw new \Exception("The reply failed: ".implode(", ", $retarray));
        
        $matches = array();
        $responseTime = 0;

        /*
         * Format of answer:
         * 
         * Foobar: some stuff
         * Time stamp: 21.08.2010 blabla GMT
         * Somestuff: Yayayayaya
         */
        
        if (!is_array($retarray)) {
            throw new \RuntimeException('$retarray must be an array.');
        }
        
        foreach ($retarray as $retline)
        {
            if (preg_match("~^Time\sstamp\:\s(.*)~", $retline, $matches))
            {
                // try to automatically convert time to unique unix timestamp
                $responseTime = strtotime($matches[1]);
                // workaround for faulty php strtotime function, that does not handle times in format "Feb 25 23:29:13.331 2015 GMT"
                // currently this accounts for the format used presumably by Universign.eu
                if(!$responseTime) {
                    $date = DateTime::createFromFormat("M j H:i:s.u Y T", $matches[1]);
                    if($date) {
                        $responseTime = $date->getTimestamp();
                    } else {
                        $responseTime = False;
                    }
                }
                break;      
            }
        }

        if (!$responseTime)
            throw new \Exception("The Timestamp was not found"); 
        
        /* Return formatted time as this is, what we will store in the database.
         * PHP will take care of correct timezone conversions (if configured correctly)
         */
        return date("Y-m-d H:i:s", $responseTime);
    }
    
    /**
     * Request a timestamp and parse the response
     */    
    private function generateToken () {   
        if (is_null($this->requestfilePath)) {
            throw new \Exception("Cannot create new timestamp token! No data was provided during initialization!");
        } elseif (!file_exists($this->requestfilePath)) {
            throw new \Exception("The Requestfile was not found: ". $this->requestfilePath);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->stampprovider);
        // if login and password are set, pass them to curl
        if (!is_null($this->stamplogin) && !is_null($this->stamppassword)) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->stamplogin.":".$this->stamppassword);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($this->requestfilePath));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/timestamp-query'));
        //Be honest about our user agent instead of faking an ancienct IE
        curl_setopt($ch, CURLOPT_USERAGENT, "eLabFTW/1.1.0");
        $binaryResponseString = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($status != 200 || !strlen($binaryResponseString))
            // return false if request fails. Must be catched by calling function!
            return false;
        
        $base64ResponseString = base64_encode($binaryResponseString);
        
        $this->binaryResponseString = $binaryResponseString;
        $this->base64ResponseString = $base64ResponseString;
        $this->responseTime = $this->getTimestampFromAnswer($base64ResponseString);
    }
    
    /**
    * Run OpenSSL via exec() with a provided command
    * @return array 
    */
    private function runOpenSSL($cmd) {
        $retarray = array();
        exec("openssl " . $cmd." 2>&1", $retarray, $retcode);
        
        return array("retarray" => $retarray,
                     "retcode" => $retcode);
    }
    
    /**
     * Validates a file against its timestamp and optionally check a provided time for consistence with the time encoded 
     * in the timestamp itself.
     *
     * @param int $timeToCheck The response time, which should be checked
     * @return bool
     */
    public function validate ($timeToCheck = NULL)
    {       
        if (!is_file($this->responsefile_path))
            throw new \Exception("There was no response-string");    
            
        if (!intval($this->responseTime))
            throw new \Exception("There is no valid response-time given");
            
        if (!file_exists($this->stampcert))
            throw new \Exception("The TSA-Certificate could not be found");

        $cmd = "ts -verify -data ".escapeshellarg($this->data)." -in ".escapeshellarg($this->responsefile_path)." -CAfile ".escapeshellarg($this->stampcert);
        
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
        
        if ($retcode === 0 && strtolower(trim($retarray[0])) == "verification: ok")
        {
        
            if (!is_null($timeToCheck)) {
                if ($timeToCheck != $this->responseTime) {
                    throw new \Exception("The response time of the request was changed");
                }
            }
            return true;
        }

        if (!is_array($retarray)) {
            throw new \RuntimeException('$retarray must be an array.');
        }
        
        foreach ($retarray as $retline)
        {
            if (stripos($retline, "message imprint mismatch") !== false)
                return false;
        }

        throw new \Exception("Systemcommand failed: ".implode(", ", $retarray));
    }
}
