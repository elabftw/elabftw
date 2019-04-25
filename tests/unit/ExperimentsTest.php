<?php
namespace Elabftw\Models;

use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\IllegalActionException;

class ExperimentsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1);
        $this->Experiments = new Experiments($this->Users);
    }

    public function testCreateAndDestroy()
    {
        $new = $this->Experiments->create(0);
        $this->assertTrue((bool) Tools::checkId($new));
        $this->Experiments->setId($new);
        $this->Experiments->canOrExplode('write');
        $this->Experiments->toggleLock();
        $this->Experiments->destroy();
        $this->Templates = new Templates($this->Users);
        $this->Templates->create('my template', 'is so cool', '1');
        $new = $this->Experiments->create('1');
        $this->assertTrue((bool) Tools::checkId($new));
        $this->Experiments = new Experiments($this->Users, $new);
        $this->Experiments->destroy();
    }

    public function testSetId()
    {
        $this->expectException(IllegalActionException::class);
        $this->Experiments->setId(0);
    }

    public function testRead()
    {
        $this->Experiments->setId('1');
        $this->Experiments->canOrExplode('read');
        $experiment = $this->Experiments->read();
        $this->assertTrue(is_array($experiment));
        $this->assertEquals('Experiment 1', $experiment['title']);
        $this->assertEquals('20160729', $experiment['date']);
    }

    public function testReadRelated()
    {
        $this->Experiments->setId(1);
        $this->Experiments->Links->create(1);
        $this->assertTrue(is_array($this->Experiments->readRelated(1)));
    }

    public function testUpdate()
    {
        $new = $this->Experiments->create(0);
        $this->Experiments->setId($new);
        $this->Experiments->canOrExplode('write');
        $this->assertEquals($new, $this->Experiments->id);
        $this->assertEquals(1, $this->Experiments->Users->userData['userid']);
        $this->Experiments->update('Untitled', '20160729', '<p>Body</p>');
    }

    public function testUpdateVisibility()
    {
        $this->Experiments->setId(1);
        $this->Experiments->canOrExplode('write');
        $this->Experiments->updateVisibility('public');
        $this->Experiments->updateVisibility('organization');
        $this->Experiments->updateVisibility('team');
        $this->Experiments->updateVisibility('user');
        $this->Experiments->updateVisibility(1);
    }

    public function testUpdateCategory()
    {
        $this->Experiments->setId(1);
        $this->Experiments->canOrExplode('write');
        $this->Experiments->updateCategory(3);
    }

    public function testDuplicate()
    {
        $this->Experiments->setId(1);
        $this->Experiments->canOrExplode('read');
        $this->assertIsInt($this->Experiments->duplicate());
    }
}
