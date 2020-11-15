<?php declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Exceptions\ImproperActionException;
use function getenv;

class TeamsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $Users = new Users(1, 1);
        $this->Teams= new Teams($Users);
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
        $this->Teams->destroy($id);
        // try to destroy a team with data
        $this->expectException(ImproperActionException::class);
        $this->Teams->destroy(1);
    }

    public function testGetAllStats()
    {
        $stats = $this->Teams->getAllStats();
        $this->assertTrue(is_array($stats));
        if (getenv('CIRCLE_BUILD_URL')) {
            $this->assertEquals(9, $stats['totusers']);
        } else {
            $this->assertEquals(8, $stats['totusers']);
        }
    }
}
