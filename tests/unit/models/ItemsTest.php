<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use function date;
use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\EntityParams;
use Elabftw\Services\Check;

class ItemsTest extends \PHPUnit\Framework\TestCase
{
    private Items $Items;

    protected function setUp(): void
    {
        $this->Items= new Items(new Users(1, 1));
    }

    public function testCreateAndDestroy(): void
    {
        $new = $this->Items->create(new EntityParams('1'));
        $this->assertTrue((bool) Check::id($new));
        $this->Items->setId($new);
        $this->Items->destroy();
    }

    public function testRead(): void
    {
        $new = $this->Items->create(new EntityParams('1'));
        $this->Items->setId($new);
        $this->Items->canOrExplode('read');
        $this->assertTrue(is_array($this->Items->entityData));
        $this->assertEquals('Untitled', $this->Items->entityData['title']);
        $this->assertEquals(date('Y-m-d'), $this->Items->entityData['date']);
    }

    public function testUpdate(): void
    {
        $new = $this->Items->create(new EntityParams('1'));
        $this->Items->setId($new);
        $this->Items->update(new EntityParams('Items item 1', 'title'));
        $this->Items->update(new EntityParams('20160729', 'date'));
        $this->Items->update(new EntityParams('pwet', 'body'));
    }

    public function testUpdateRating(): void
    {
        $this->Items->setId(1);
        $this->Items->updateRating(1);
    }

    public function testDuplicate(): void
    {
        $this->Items->setId(1);
        $this->Items->canOrExplode('read');
        $this->assertTrue((bool) Check::id($this->Items->duplicate()));
    }

    public function testToggleLock(): void
    {
        $new = $this->Items->create(new EntityParams('1'));
        $this->Items->setId($new);

        // lock
        $this->Items->toggleLock();
        $item = $this->Items->read(new ContentParams());
        $this->assertTrue((bool) $item['locked']);
        // unlock
        $this->Items->toggleLock();
        $item = $this->Items->read(new ContentParams());
        $this->assertFalse((bool) $item['locked']);
    }
}
