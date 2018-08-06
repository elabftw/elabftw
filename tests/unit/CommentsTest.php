<?php
namespace Elabftw\Elabftw;

use PDO;

class CommentsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->Users = new Users(1);
        $this->Entity = new Experiments($this->Users, 1);

        // create mock object for Email because we don't want to actually send emails
        $this->mockEmail = $this->getMockBuilder('\Elabftw\Elabftw\Email')
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
        $id = $this->Entity->Comments->create('Ohai');
        $this->assertInternalType("int", $id);
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Entity->Comments->readAll()));
    }

    public function testUpdate()
    {
        $this->assertTrue($this->Entity->Comments->Update('Updated', 'comment_1'), 1);
        $this->assertFalse($this->Entity->Comments->Update('a', 'comment_1'), 1);
    }

    public function testDestroy()
    {
        $this->assertTrue($this->Entity->Comments->destroy(1, 1));
    }

    public function testDestroyAll()
    {
        $this->assertTrue($this->Entity->Comments->destroyAll());
        $this->assertTrue(empty($this->Entity->Comments->readAll()));
    }
}
