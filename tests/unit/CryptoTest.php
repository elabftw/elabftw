<?php
class CryptoTest extends \PHPUnit_Framework_TestCase
{
    // Test the Crypto class
    public function testCrypto()
    {
        $data = 'mypassword';
        $crypto = new \Elabftw\Elabftw\Crypto();

        $this->assertEquals(16, strlen($crypto->getIv()));
        $this->assertEquals(128, strlen($crypto->getSecretKey()));
        $this->assertEquals($data, $crypto->decrypt($crypto->encrypt($data)));
    }
}
