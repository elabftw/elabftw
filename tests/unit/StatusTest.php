<?php
namespace Elabftw\Elabftw;

use PDO;

class StatusTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Status = new Status(1);
    }

    public function testCreate()
    {
        $new = $this->Status->create('', 'fffccc');
        $this->assertTrue((bool) Tools::checkId($new));
    }

    public function testReadAllAndUpdate()
    {
        $all = $this->Status->readAll();
        $this->assertTrue(is_array($all));
        $last = array_pop($all);
        $this->assertEquals('fffccc', $this->Status->readColor($last['id']));
    }

    public function testUpdate()
    {
        $this->assertTrue($this->Status->update($this->Status->create('Yep', 'fffaaa'), 'New name', 'aaabbb', 'on'));
        $this->assertTrue($this->Status->update($this->Status->create('Yep2', 'fffaaa'), 'New name', 'aaabbb', false));
    }
}
