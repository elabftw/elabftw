<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ParamsProcessor;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Services\Check;

class ExperimentsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Experiments = new Experiments($this->Users);
    }

    public function testCreateAndDestroy()
    {
        $new = $this->Experiments->create(new ParamsProcessor(array('id' => 0)));
        $this->assertTrue((bool) Check::id($new));
        $this->Experiments->setId($new);
        $this->Experiments->canOrExplode('write');
        $this->Experiments->toggleLock();
        $this->Experiments->destroy();
        $this->Templates = new Templates($this->Users);
        $this->Templates->create(new ParamsProcessor(array('name' => 'my template', 'template' => 'is so cool')));
        $new = $this->Experiments->create(new ParamsProcessor(array('id' => 1)));
        $this->assertTrue((bool) Check::id($new));
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
        $new = $this->Experiments->create(new ParamsProcessor(array('id' => 0)));
        $this->Experiments->setId($new);
        $this->Experiments->canOrExplode('read');
        $experiment = $this->Experiments->read();
        $this->assertTrue(is_array($experiment));
        $this->assertEquals('Untitled', $experiment['title']);
        //$this->assertEquals('20160729', $experiment['date']);
    }

    public function testUpdate()
    {
        $new = $this->Experiments->create(new ParamsProcessor(array('id' => 0)));
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
        $this->Experiments->updatePermissions('read', 'public');
        $this->Experiments->updatePermissions('read', 'organization');
        $this->Experiments->updatePermissions('write', 'team');
        $this->Experiments->updatePermissions('write', 'public');
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
