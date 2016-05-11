<?php
/**
 * \Elabftw\Elabftw\CryptoWrapper
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Defuse\Crypto\Crypto as Crypto;

/**
 * Used for decrypting and encrypting passwords
 */
class CryptoWrapper
{
    /**
     * Load the binary secret key from config.php
     *
     * @return string raw binary string
     */
    private function getSecretKey()
    {
        return Crypto::hexToBin(SECRET_KEY);
    }

    /**
     * Decrypt something
     *
     * @param string $ciphertext The hexadecimal string
     * @return string cleartext string
     */
    public function decrypt($ciphertext)
    {
        return Crypto::decrypt(Crypto::hexToBin($ciphertext), $this->getSecretKey());
    }

    /**
     * Encrypt something
     *
     * @param string $cleartext
     * @return string hexadecimal representation of crypted string
     */
    public function encrypt($cleartext)
    {
        return Crypto::binToHex(Crypto::encrypt($cleartext, $this->getSecretKey()));
    }
}
