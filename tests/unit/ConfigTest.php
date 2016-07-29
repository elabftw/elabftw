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
        $this->assertTrue(is_array($this->Config->read()));
        $this->assertEquals('sha256', $this->Config->read('stamphash'));
    }
}
