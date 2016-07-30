<?php
namespace Elabftw\Elabftw;

use PDO;

class RevisionsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Revisions = new Revisions('experiments', 1, 1);
    }

    public function testCreate()
    {
        $this->assertTrue($this->Revisions->create('Ohai', 1));
    }

    public function testRead()
    {
        $this->assertTrue(is_array($this->Revisions->read()));
    }

    public function testReadCount()
    {
        $this->assertInternalType('int', $this->Revisions->readCount());
        $this->Revisions = new Revisions('items', 1, 1);
        $this->assertInternalType('int', $this->Revisions->readCount());
    }

    public function testRestore()
    {
        $this->assertTrue($this->Revisions->restore(1));
        /*
        $this->Experiment = new Experiments(1, 1);
        $this->Experiment->toggleLock();
        $this->setExpectedException('Exception');
        $this->Revisions->restore(1);
         */
    }
}
