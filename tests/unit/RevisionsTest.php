<?php
namespace Elabftw\Elabftw;

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
        $this->Experiment = new Experiments(1, 1);
        $new = $this->Experiment->create();
        $this->Experiment->setId($new);
        $this->Revisions = new Revisions('experiments', $new, 1);
        $this->assertTrue($this->Revisions->create('Ohai', $new));
        $this->assertTrue($this->Revisions->restore($new));
    }
}
