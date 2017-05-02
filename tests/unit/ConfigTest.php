<?php
namespace Elabftw\Elabftw;

use PDO;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Config= new Config();
    }

    public function testRead()
    {
        $this->assertTrue(is_array($this->Config->configArr));
        $this->assertEquals('sha256', $this->Config->configArr['stamphash']);
    }

    public function testUpdate()
    {
        $post = array(
            'smtp_address' => "smtp.mailgun.org",
            'smtp_encryption' => "tls",
            'smtp_password' => "yep",
            'smtp_port' => 587,
            'stampcert' => "app/dfn-cert/pki.dfn.pem",
            'stamppass' => "",
            'login_tries' => 15,
            'ban_time' => 42
        );

        $this->assertTrue($this->Config->update($post));
        // now try bad path to cert
        $post = array('stampcert' => 'invalid/path');
        $this->setExpectedException('Exception');
        $this->Config->update($post);
        // try bad value for ban_time
        $post = array('ban_time' => 'invalid');
        $this->setExpectedException('Exception');
        $this->Config->update($post);
        // try bad value for login_tries
        $post = array('login_tries' => 'invalid');
        $this->setExpectedException('Exception');
        $this->Config->update($post);
        // try with no password
        $post = array('smtp_password' => '');
        $this->setExpectedException('Exception');
        $this->Config->update($post);
    }

    public function testDestroyStamppass()
    {
        $this->assertTrue($this->Config->destroyStamppass());
    }

    public function testReset()
    {
        $this->assertTrue($this->Config->reset());
    }
}
