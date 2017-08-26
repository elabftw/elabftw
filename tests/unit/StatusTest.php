<?php
namespace Elabftw\Elabftw;

use PDO;

class StatusTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Status = new Status(new Users(1));
    }

    public function testCreate()
    {
        $new = $this->Status->create('', 'fffccc', 1);
        $this->assertTrue((bool) Tools::checkId($new));
    }

    public function testReadAll()
    {
        $all = $this->Status->readAll();
        $this->assertTrue(is_array($all));
    }

    public function testUpdate()
    {
        $this->assertTrue($this->Status->update($this->Status->create('Yep', 'fffaaa', 1), 'New name', 'fffccc', 0, 'on'));
        $this->assertTrue($this->Status->update($this->Status->create('Yep2', 'fffaaa', 1), 'New name', 'fffccc', 1, false));
    }
}
