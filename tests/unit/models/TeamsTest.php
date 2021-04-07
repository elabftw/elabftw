<?php declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Exceptions\ImproperActionException;

class TeamsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Teams= new Teams(new Users(1, 1));
    }

    public function testCreate()
    {
        $this->assertIsInt($this->Teams->create('Test team'));
    }

    public function testRead()
    {
        $this->assertTrue(is_array($this->Teams->read()));
    }

    public function testDestroy()
    {
        $id = $this->Teams->create('Destroy me');
        $this->Teams->setId($id);
        $this->Teams->destroy();
        // try to destroy a team with data
        $this->Teams->setId(1);
        $this->expectException(ImproperActionException::class);
        $this->Teams->destroy();
    }

    public function testGetAllStats()
    {
        $stats = $this->Teams->getAllStats();
        $this->assertTrue(is_array($stats));
        $this->assertEquals('0', $stats['totxpts']);
    }
}
