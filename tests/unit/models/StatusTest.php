<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Check;

class StatusTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Status = new Status(new Users(1));
    }

    public function testCreate()
    {
        $new = $this->Status->create('', '#fffccc', 1);
        $this->assertTrue((bool) Check::id($new));
    }

    public function testReadAll()
    {
        $all = $this->Status->readAll();
        $this->assertTrue(is_array($all));
    }

    public function testUpdate()
    {
        $this->Status->update($this->Status->create('Yep', '#fffaaa', 1), 'New name', '#fffccc', 0, 1);
        $this->Status->update($this->Status->create('Yep2', '#fffaaa', 1), 'New name', '#fffccc', 1, 0);
    }

    public function testReadColor()
    {
        $this->assertEquals('0096ff', $this->Status->readColor(1));
    }

    public function testIsTimestampable()
    {
        $this->assertFalse($this->Status->isTimestampable(1));
    }

    public function testDestroy()
    {
        $this->Status->destroy(2);
        $this->expectException(ImproperActionException::class);
        $this->Status->destroy(1);
    }

    public function testDestroyAll()
    {
        $this->Status->destroyAll();
    }
}
