<?php
/**
 * \Elabftw\Elabftw\LegacyCrypto
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 * @deprecated now we use defuse/php-encryption
 */
namespace Elabftw\Elabftw;

/**
 * Used for decrypting and encrypting passwords
 */
class LegacyCrypto
{
    /** the initialization vector, a string of bytes */
    public $iv;
    /** size of the iv */
    private $ivLength = 16;

    /** a sha512 string */
    public $secretKey;

    /** the cipher that will be used */
    private $method;

    /**
     * Choose a method, set the secretkey and iv
     */
    public function __construct()
    {
        $this->method = $this->getMethod();
        $this->secretKey = $this->getSecretKey();
        $this->iv = $this->getIv();
    }

    /**
     * Select which method we will use for our crypto stuff
     *
     * @return string The selected method that is available
     */
    private function getMethod()
    {
        $availableMethods = openssl_get_cipher_methods();

        if (in_array('AES-256-CBC-HMAC-SHA256', $availableMethods)) {
            return 'AES-256-CBC-HMAC-SHA256';
        } elseif (in_array('AES-256-CBC', $availableMethods)) {
            return 'AES-256-CBC';
        }
        // just take the first one coming, it's better than nothing
        return $availableMethods[0];
    }

    /**
     * The secret key is a sha512 sum based on uniqid()
     *
     * @return string The secret key taken from config or generated
     */
    private function getSecretKey()
    {
        if (defined('SECRET_KEY')) {
            return SECRET_KEY;
        }
        return hash("sha512", uniqid(rand(), true));
    }

    /**
     * The IV is in binary and stored in hex in config file
     *
     * @return string binary IV
     */
    private function getIv()
    {
        if (defined('IV')) {
            return hex2bin(IV);
        }
        $crypto_strong = true;
        return openssl_random_pseudo_bytes($this->ivLength, $crypto_strong);
    }

    /**
     * Encrypt something
     *
     * @param string $data the data to encrypt
     * @return string encrypted data
     */
    public function encrypt($data)
    {
        return openssl_encrypt($data, $this->method, $this->secretKey, 0, $this->iv);
    }

    /**
     * Decrypt something
     *
     * @param string $data the data to decrypt
     * @return string decrypted data
     */
    public function decrypt($data)
    {
        return openssl_decrypt($data, $this->method, $this->secretKey, 0, $this->iv);
    }
}
