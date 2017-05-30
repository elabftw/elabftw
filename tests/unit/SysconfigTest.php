<?php
namespace Elabftw\Elabftw;

use PDO;

class SysconfigTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Sysconfig= new Sysconfig(new Email(new Config()));
    }

    public function testTestEmailSend()
    {
        //$this->assertTrue($this->Sysconfig->testEmailSend('phpunitelabftw@yopmail.com'));
        $this->expectException(\Exception::class);
        $this->Sysconfig->testEmailSend('bad email');
    }

    public function testMassEmail()
    {
        //$this->assertTrue($this->Sysconfig->massEmail('hello', 'from phpunit') === 1);
    }
}
