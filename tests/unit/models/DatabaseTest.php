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
use Elabftw\Services\Check;
use Elabftw\Services\Filter;

class DatabaseTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Database= new Database($this->Users);
    }

    public function testCreateAndDestroy()
    {
        $new = $this->Database->create(new ParamsProcessor(array('id' => 1)));
        $this->assertTrue((bool) Check::id($new));
        $this->Database->setId($new);
        $this->Database->destroy();
    }

    public function testSetId()
    {
        $this->expectException(\TypeError::class);
        $this->Database->setId('alpha');
    }

    public function testRead()
    {
        $new = $this->Database->create(new ParamsProcessor(array('id' => 1)));
        $this->Database->setId($new);
        $this->Database->canOrExplode('read');
        $this->assertTrue(is_array($this->Database->entityData));
        $this->assertEquals('Untitled', $this->Database->entityData['title']);
        $this->assertEquals(Filter::kdate(), $this->Database->entityData['date']);
    }

    public function testUpdate()
    {
        $new = $this->Database->create(new ParamsProcessor(array('id' => 1)));
        $this->Database->setId($new);
        $this->Database->update('Database item 1', '20160729', 'body', 1);
    }

    public function testUpdateRating()
    {
        $this->Database->setId(1);
        $this->Database->updateRating(1);
    }

    public function testDuplicate()
    {
        $this->Database->setId(1);
        $this->Database->canOrExplode('read');
        $this->assertTrue((bool) Check::id($this->Database->duplicate(1)));
    }

    public function testToggleLock()
    {
        $new = $this->Database->create(new ParamsProcessor(array('id' => 1)));
        $this->Database->setId($new);

        // lock
        $this->Database->toggleLock();
        $item = $this->Database->read();
        $this->assertTrue((bool) $item['locked']);
        // unlock
        $this->Database->toggleLock();
        $item = $this->Database->read();
        $this->assertFalse((bool) $item['locked']);
    }
}
