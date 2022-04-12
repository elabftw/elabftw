<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\StatusParams;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Check;

class StatusTest extends \PHPUnit\Framework\TestCase
{
    private Status $Status;

    protected function setUp(): void
    {
        $this->Status = new Status(1, 1);
    }

    public function testCreate(): void
    {
        $new = $this->Status->create(new StatusParams('New status', '#29AEB9', false, true));
        $this->assertTrue((bool) Check::id($new));
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->Status->read(new ContentParams()));
    }

    public function testUpdate(): void
    {
        $id = $this->Status->create(new StatusParams('Yep', '#29AEB9', false, true));
        $Status = new Status(1, $id);
        $Status->update(new StatusParams('Updated', '#121212', true, false));
        $status = $Status->read(new ContentParams());
        $this->assertEquals('Updated', $status['category']);
        $this->assertEquals('121212', $status['color']);
        $this->assertTrue((bool) $status['is_timestampable']);
        $this->assertFalse((bool) $status['is_default']);
        $Status->update(new StatusParams('Updated', '#121212', true, true));
        $status = $Status->read(new ContentParams());
        $this->assertTrue((bool) $status['is_default']);
        // undo changes so that MakeTimestampTest.php:testNonTimestampableExperiment works
        $Status->update(new StatusParams('Updated', '#121212', false, true));
    }

    public function testDestroy(): void
    {
        $id = $this->Status->create(new StatusParams('Yep1', '#29AEB9', false, false));
        $Status = new Status(1, $id);
        $this->assertTrue($Status->destroy());
        $this->expectException(ImproperActionException::class);
        $this->Status->destroy();
    }
}
