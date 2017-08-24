<?php
namespace Elabftw\Elabftw;

use PDO;

class LogsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Logs= new Logs();
    }

    public function testCreate()
    {
        $this->assertTrue($this->Logs->create('Error', 1, 'Something bad happened!'));
        $this->assertTrue(is_array($this->Logs->readAll()));
        $this->assertTrue($this->Logs->destroyAll());
    }
}
