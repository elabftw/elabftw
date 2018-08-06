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
        $this->mockEmail = $this->getMockBuilder(\Elabftw\Elabftw\Email::class)
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
        $this->assertTrue($this->Comments->Update('Updated', 'comment_1'), 1);
        $this->assertFalse($this->Comments->Update('a', 'comment_1'), 1);
    }

    public function testDestroy()
    {
        $this->assertTrue($this->Comments->destroy(1, 1));
    }

    public function testDestroyAll()
    {
        $this->assertTrue($this->Comments->destroyAll());
        $this->assertTrue(empty($this->Comments->readAll()));
    }
}
