<?php
namespace Elabftw\Models;

use Elabftw\Exceptions\ImproperActionException;

class CommentsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->Users = new Users(1);
        $this->Entity = new Experiments($this->Users, 1);

        // create mock object for Email because we don't want to actually send emails
        $this->mockEmail = $this->getMockBuilder(\Elabftw\Services\Email::class)
             ->disableOriginalConstructor()
             ->setMethods(array('send'))
             ->getMock();

        $this->mockEmail->expects($this->any())
             ->method('send')
             ->will($this->returnValue(1));

        $this->Comments = new Comments($this->Entity, $this->mockEmail);
    }

    public function testCreate()
    {
        $id = $this->Comments->create('Ohai');
        $this->assertInternalType("int", $id);
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Comments->readAll()));
    }

    public function testUpdate()
    {
        $this->Comments->Update('Updated', 1);
        // too short comment
        $this->expectException(ImproperActionException::class);
        $this->Comments->Update('a', 1);
    }

    public function testDestroy()
    {
        $this->Comments->destroy(1);
    }
}
