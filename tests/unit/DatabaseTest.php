<?php
namespace Elabftw\Elabftw;

use PDO;

class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Database= new Database(1);
    }

    public function testCreate()
    {
        $this->assertTrue((bool) Tools::checkId($this->Database->create(1, 1)));
    }

    public function testSetId()
    {
        $this->setExpectedException('Exception');
        $this->Database->setId('alpha');
    }

    public function testRead()
    {
        $this->Database->setId(1);
        $item = $this->Database->read();
        $this->assertTrue(is_array($item));
        $this->assertEquals('Database item 1', $item['title']);
        $this->assertEquals('20160729', $item['date']);
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Database->readAll()));
    }
}
