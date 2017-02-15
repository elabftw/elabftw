<?php
namespace Elabftw\Elabftw;

class ExperimentsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Users = new Users(1);
        $this->Experiments = new Experiments($this->Users);
    }

    public function testCreateAndDestroy()
    {
        $new = $this->Experiments->create();
        $this->assertTrue((bool) Tools::checkId($new));
        $this->Experiments->setId($new);
        $this->Experiments->populate();
        $this->Experiments->toggleLock();
        $this->assertTrue($this->Experiments->destroy());
        $this->Templates = new Templates($this->Users);
        $this->Templates->create('my template', 'is so cool', '1');
        $new = $this->Experiments->create('1');
        $this->assertTrue((bool) Tools::checkId($new));
        $this->Experiments = new Experiments($this->Users, $new);
        $this->assertTrue($this->Experiments->destroy());
    }

    public function testSetId()
    {
        $this->setExpectedException('Exception');
        $this->Experiments->setId('alpha');
    }

    public function testRead()
    {
        $this->Experiments->setId('1');
        $this->Experiments->populate();
        $experiment = $this->Experiments->read();
        $this->assertTrue(is_array($experiment));
        $this->assertEquals('Untitled', $experiment['title']);
        $this->assertEquals('20160729', $experiment['date']);
        $this->setExpectedException('Exception');
        $this->Experiments->setId('a9999999999');
    }

    public function testReadRelated()
    {
        $this->Experiments->setId(1);
        $Links = new Links($this->Experiments);
        $Links->create(1);
        $this->assertTrue(is_array($this->Experiments->readRelated(1)));
    }

    public function testUpdate()
    {
        $this->Experiments->setId(1);
        $this->Experiments->populate();
        $this->assertEquals(1, $this->Experiments->id);
        $this->assertEquals(1, $this->Experiments->Users->userid);
        $this->assertTrue($this->Experiments->update('Untitled', '20160729', '<p>Body</p>'));
    }

    public function testUpdateVisibility()
    {
        $this->Experiments->setId(1);
        $this->Experiments->populate();
        $this->assertTrue($this->Experiments->updateVisibility('public'));
        $this->assertTrue($this->Experiments->updateVisibility('organization'));
        $this->assertTrue($this->Experiments->updateVisibility('team'));
        $this->assertTrue($this->Experiments->updateVisibility('user'));
        $this->assertTrue($this->Experiments->updateVisibility(1));
    }

    public function testUpdateStatus()
    {
        $this->Experiments->setId(1);
        $this->Experiments->populate();
        $this->assertTrue($this->Experiments->updateStatus(3));
    }

    public function testDuplicate()
    {
        $this->Experiments->setId(1);
        $this->Experiments->populate();
        $this->assertInternalType("int", $this->Experiments->duplicate());
    }
}
