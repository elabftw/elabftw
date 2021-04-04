<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateStatus;
use Elabftw\Elabftw\DestroyParams;
use Elabftw\Elabftw\UpdateStatus;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Check;

class StatusTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Status = new Status(1);
    }

    public function testCreate()
    {
        $new = $this->Status->create(new CreateStatus('New status', '#29AEB9', false, true));
        $this->assertTrue((bool) Check::id($new));
    }

    public function testRead()
    {
        $all = $this->Status->read();
        $this->assertTrue(is_array($all));
    }

    public function testUpdate()
    {
        $params = new CreateStatus('Yep', '#29AEB9', false, true);
        $id = $this->Status->create(new CreateStatus('Yep', '#29AEB9', false, true));
        $this->Status->update(new UpdateStatus($id, 'Updated', '#121212', true, false));
        $ourStatus = array_filter($this->Status->read(), function ($s) use ($id) {
            return ((int) $s['category_id']) === $id;
        });
        $status = array_pop($ourStatus);
        $this->assertEquals('Updated', $status['category']);
        $this->assertEquals('121212', $status['color']);
        $this->assertTrue((bool) $status['is_timestampable']);
        $this->assertFalse((bool) $status['is_default']);
    }

    public function testReadColor()
    {
        $this->assertEquals('29aeb9', strtolower($this->Status->readColor(1)));
    }

    public function testDestroy()
    {
        $this->expectException(ImproperActionException::class);
        $this->Status->destroy(new DestroyParams(1));
    }
}
