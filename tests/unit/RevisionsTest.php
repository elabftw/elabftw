<?php
namespace Elabftw\Elabftw;

class RevisionsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->Users = new Users(1);
        $this->Experiments = new Experiments($this->Users, 1);
        $this->Revisions = new Revisions($this->Experiments);
    }

    public function testCreate()
    {
        $this->assertTrue($this->Revisions->create('Ohaiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii'));
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Revisions->readAll()));
    }

    public function testReadCount()
    {
        $this->assertInternalType('int', $this->Revisions->readCount());
        $this->Revisions = new Revisions(new Database($this->Users, '1'));
        $this->assertInternalType('int', $this->Revisions->readCount());
    }

    public function testRestore()
    {
        $this->Experiment = new Experiments($this->Users, '1');
        $new = $this->Experiment->create();
        $this->Experiment->setId($new);
        $this->Revisions = new Revisions($this->Experiment);
        $this->assertTrue($this->Revisions->create('Ohai'));
        $this->assertTrue($this->Revisions->restore($new));
        //$this->Experiments->toggleLock();
        //$this->expectException(\Exception::class);
        //$this->Revisions->restore(2);
    }
    public function testDestroy()
    {
        $this->assertFalse($this->Revisions->destroy(1));
    }

    public function testDestroyAll()
    {
        $this->assertFalse($this->Revisions->destroyAll());
    }
}
