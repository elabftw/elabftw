<?php
namespace Elabftw\Elabftw;

class ReleaseCheckTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->ReleaseCheck = new ReleaseCheck(new Config());
    }

    public function testgetUpdatesIni()
    {
        $this->assertTrue($this->ReleaseCheck->getUpdatesIni());
    }

    public function testUpdateIsAvailable()
    {
        $this->assertInternalType('bool', $this->ReleaseCheck->updateIsAvailable());
    }
}
