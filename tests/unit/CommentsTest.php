<?php
namespace Elabftw\Elabftw;

use PDO;

class CommentsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Experiments = new Experiments(1, 1, 1);
        $this->Comments = new Comments($this->Experiments);
    }

    public function testCreate()
    {
        $this->assertTrue($this->Comments->create('Ohai'));
    }

    public function testRead()
    {
        $this->assertTrue(is_array($this->Comments->read()));
    }

    public function testUpdate()
    {
        $this->Comments = new Comments($this->Experiments, 1);
        $this->assertTrue($this->Comments->Update('Udpated'));
        $this->assertFalse($this->Comments->Update('a'));
    }

    public function testDestroy()
    {
        $this->assertTrue($this->Comments->destroy());
    }

    public function testDestroyAll()
    {
        $this->assertTrue($this->Comments->destroyAll());
        $this->assertFalse(is_array($this->Comments->read()));
    }
}
