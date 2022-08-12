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
        $new = $this->Items->create(1);
        $this->assertTrue((bool) Check::id($new));
        $this->Items->setId($new);
        $this->Items->destroy();
    }

    public function testRead(): void
    {
        $new = $this->Items->create(1);
        $this->Items->setId($new);
        $this->Items->canOrExplode('read');
        $this->assertTrue(is_array($this->Items->entityData));
        $this->assertEquals('Untitled', $this->Items->entityData['title']);
        $this->assertEquals(date('Y-m-d'), $this->Items->entityData['date']);
    }

    public function testUpdate(): void
    {
        $new = $this->Items->create(1);
        $this->Items->setId($new);
        $entityData = $this->Items->patch(array('title' => 'Untitled', 'date' => '20160729', 'body' => '<p>Body</p>'));
        $this->assertEquals('Untitled', $entityData['title']);
        $this->assertEquals('2016-07-29', $entityData['date']);
        $this->assertEquals('<p>Body</p>', $entityData['body']);
    }

    public function testDuplicate(): void
    {
        $this->Items->setId(1);
        $this->Items->canOrExplode('read');
        $this->assertTrue((bool) Check::id($this->Items->duplicate()));
    }

    public function testToggleLock(): void
    {
        $new = $this->Items->create(1, array('locked'));
        $this->Items->setId($new);

        // lock
        $item =$this->Items->toggleLock();
        $this->assertTrue((bool) $item['locked']);
        // unlock
        $item = $this->Items->toggleLock();
        $this->assertFalse((bool) $item['locked']);
    }
}
