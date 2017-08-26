<?php
namespace Elabftw\Elabftw;

use PDO;

class CommentsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Users = new Users(1);
        $this->Entity = new Experiments($this->Users, 1);
    }

    public function testCreate()
    {
        $this->assertEquals($this->Entity->Comments->create('Ohai'), 1);
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Entity->Comments->readAll()));
    }

    public function testUpdate()
    {
        $this->assertTrue($this->Entity->Comments->Update('Udpated', 1), 1);
        $this->assertFalse($this->Entity->Comments->Update('a', 1), 1);
    }

    public function testDestroy()
    {
        $this->assertTrue($this->Entity->Comments->destroy(1, 1));
    }

    public function testDestroyAll()
    {
        $this->assertTrue($this->Entity->Comments->destroyAll());
        $this->assertFalse(is_array($this->Entity->Comments->readAll()));
    }
}
