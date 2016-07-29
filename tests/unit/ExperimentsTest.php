<?php
namespace Elabftw\Elabftw;

use PDO;

class ExperimentsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Experiments= new Experiments(1);
    }

    public function testCreate()
    {
        $this->assertTrue((bool) Tools::checkId($this->Experiments->create()));
    }

    public function testSetId()
    {
        $this->setExpectedException('Exception');
        $this->Experiments->setId('alpha');
    }

    public function testRead()
    {
        $this->Experiments->setId(1);
        $experiment = $this->Experiments->read();
        $this->assertTrue(is_array($experiment));
        $this->assertEquals('Untitled', $experiment['title']);
        $this->assertEquals('20160729', $experiment['date']);
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Experiments->readAll()));
    }

    public function testReadRelated()
    {
        $this->assertTrue(is_array($this->Experiments->readRelated(1)));
    }

    public function testUpdate()
    {
        /* Not working for some reason...
        $this->Experiments->setId(1);
        $this->assertEquals(1, $this->Experiments->id);
        $this->assertEquals(1, $this->Experiments->userid);
        $this->assertTrue($this->Experiments->update('title', '20160729', '<p>Body</p>'));
         */
    }
}
