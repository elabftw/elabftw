<?php
class CryptoTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    // Test the Crypto class
    public function testCrypto()
    {
        $data = 'mypassword';
        $crypto = new \Elabftw\Elabftw\Crypto();

        $this->assertEquals(16, strlen($crypto->iv));
        $this->assertEquals(128, strlen($crypto->secretKey()));
        $this->assertEquals($data, $crypto->decrypt($crypto->encrypt($data)));
    }
}
