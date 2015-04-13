<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
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
********************************************************************************/
namespace Elabftw\Elabftw;

class Crypto
{
    public $iv;
    public $secretKey;

    private $ivLength = 16;
    private $cryptoStrong = true;
    private $availableMethods;
    private $method;

    public function __construct()
    {
        $this->setMethod();
        $this->setSecretKey();
        $this->setIv();
    }

    private function setMethod()
    {
        // select the right method
        $this->availableMethods = openssl_get_cipher_methods();

        if (in_array('AES-256-CBC-HMAC-SHA256', $this->availableMethods)) {
            $this->method = 'AES-256-CBC-HMAC-SHA256';
        } elseif (in_array('AES-256-CBC', $this->availableMethods)) {
            $this->method = 'AES-256-CBC';
        } else {
            // just take the first one coming, I guess it's better than nothing.
            $this->method = $this->availableMethods[0];
        }
    }

    private function setSecretKey()
    {
        if (defined('SECRET_KEY')) {
            $this->secretKey = SECRET_KEY;
        } else {
            $this->secretKey = hash("sha512", uniqid(rand(), true));
        }
    }

    private function setIv()
    {
        if (defined('IV')) {
            $this->iv = hex2bin(IV);
        } else {
            $this->iv = openssl_random_pseudo_bytes($this->ivLength, $this->cryptoStrong);
        }
    }

    public function getIv()
    {
        return $this->iv;
    }

    public function getSecretKey()
    {
        return $this->secretKey;
    }

    public function encrypt($data)
    {
        return openssl_encrypt($data, $this->method, $this->secretKey, 0, $this->iv);
    }

    public function decrypt($data)
    {
        return openssl_decrypt($data, $this->method, $this->secretKey, 0, $this->iv);
    }
}
