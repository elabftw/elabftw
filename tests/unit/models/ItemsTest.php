<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateEntity;
use Elabftw\Elabftw\UpdateEntity;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;

class ItemsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Items= new Items($this->Users);
    }

    public function testCreateAndDestroy()
    {
        $new = $this->Items->create(new CreateEntity(1));
        $this->assertTrue((bool) Check::id($new));
        $this->Items->setId($new);
        $this->Items->destroy();
    }

    public function testSetId()
    {
        $this->expectException(\TypeError::class);
        $this->Items->setId('alpha');
    }

    public function testRead()
    {
        $new = $this->Items->create(new CreateEntity(1));
        $this->Items->setId($new);
        $this->Items->canOrExplode('read');
        $this->assertTrue(is_array($this->Items->entityData));
        $this->assertEquals('Untitled', $this->Items->entityData['title']);
        $this->assertEquals(Filter::kdate(), $this->Items->entityData['date']);
    }

    public function testUpdate()
    {
        $new = $this->Items->create(new CreateEntity(1));
        $this->Items->setId($new);
        $this->Items->update(new UpdateEntity('title', 'Items item 1'));
        $this->Items->update(new UpdateEntity('date', '20160729'));
        $this->Items->update(new UpdateEntity('body', 'pwet'));
    }

    public function testUpdateRating()
    {
        $this->Items->setId(1);
        $this->Items->updateRating(1);
    }

    public function testDuplicate()
    {
        $this->Items->setId(1);
        $this->Items->canOrExplode('read');
        $this->assertTrue((bool) Check::id($this->Items->duplicate(1)));
    }

    public function testToggleLock()
    {
        $new = $this->Items->create(new CreateEntity(1));
        $this->Items->setId($new);

        // lock
        $this->Items->toggleLock();
        $item = $this->Items->read();
        $this->assertTrue((bool) $item['locked']);
        // unlock
        $this->Items->toggleLock();
        $item = $this->Items->read();
        $this->assertFalse((bool) $item['locked']);
    }
}
