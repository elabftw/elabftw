<?php
namespace Elabftw\Elabftw;

use PDO;

class CommentsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Users = new Users(1);
        $this->Experiments = new Experiments($this->Users, 1);
        $this->Comments = new Comments($this->Experiments);
    }

    public function testCreate()
    {
        $this->assertEquals($this->Comments->create('Ohai'), 1);
    }

    public function testRead()
    {
        $this->assertTrue(is_array($this->Comments->read()));
    }

    public function testUpdate()
    {
        $this->assertTrue($this->Comments->Update('Udpated', 1), 1);
        $this->assertFalse($this->Comments->Update('a', 1), 1);
    }

    public function testDestroy()
    {
        $this->assertTrue($this->Comments->destroy(1));
    }

    public function testDestroyAll()
    {
        $this->assertTrue($this->Comments->destroyAll());
        $this->assertFalse(is_array($this->Comments->read()));
    }
}
