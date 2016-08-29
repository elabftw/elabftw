<?php
namespace Elabftw\Elabftw;

use PDO;

class ExperimentsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Experiments= new Experiments(1, 1);
    }

    public function testCreateAndDestroy()
    {
        $new = $this->Experiments->create();
        $this->assertTrue((bool) Tools::checkId($new));
        $this->Experiments = new Experiments(1, 1, $new);
        $this->Experiments->toggleLock();
        $this->assertTrue($this->Experiments->destroy());
        $this->Templates = new Templates(1);
        $this->Templates->create('my template', 'is so cool', 1);
        $new = $this->Experiments->create(1);
        $this->assertTrue((bool) Tools::checkId($new));
        $this->Experiments = new Experiments(1, 1, $new);
        $this->assertTrue($this->Experiments->destroy());
    }

    public function testSetId()
    {
        $this->setExpectedException('Exception');
        $this->Experiments->setId('alpha');
    }

    public function testRead()
    {
        $this->Experiments->setId(1);
        $experiment = $this->Experiments->read();
        $this->assertTrue(is_array($experiment));
        $this->assertEquals('Untitled', $experiment['title']);
        $this->assertEquals('20160729', $experiment['date']);
        $this->setExpectedException('Exception');
        $this->Experiments->setId(9999999999);
        $this->Experiments->read();
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Experiments->readAllFromUser()));
    }

    public function testReadAllFromTeam()
    {
        $this->assertTrue(is_array($this->Experiments->readAllFromTeam()));
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
        $this->assertEquals(1, $this->Experiments->id);
        $this->assertEquals(1, $this->Experiments->userid);
        $this->assertTrue($this->Experiments->update('Untitled', '20160729', '<p>Body</p>'));

        $this->Experiments->setId(5);
        $this->setExpectedException('Exception');
        $this->Experiments->update('o', 'o', 'o');
    }

    public function testUpdateVisibility()
    {
        $this->Experiments->setId(1);
        $this->assertTrue($this->Experiments->updateVisibility('public'));
        $this->assertTrue($this->Experiments->updateVisibility('organization'));
        $this->assertTrue($this->Experiments->updateVisibility('team'));
        $this->assertTrue($this->Experiments->updateVisibility('user'));
        $this->assertTrue($this->Experiments->updateVisibility(1));
        $this->setExpectedException('Exception');
        $this->Experiments->updateVisibility('pwet');
    }

    public function testUpdateStatus()
    {
        $this->Experiments->setId(1);
        $this->assertTrue($this->Experiments->updateStatus(3));
    }

    public function testDuplicate()
    {
        $this->Experiments->setId(1);
        $this->assertInternalType("int", $this->Experiments->duplicate());
    }
}
