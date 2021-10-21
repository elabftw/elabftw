<?php declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Exceptions\ImproperActionException;

class TeamsTest extends \PHPUnit\Framework\TestCase
{
    private Teams $Teams;

    protected function setUp(): void
    {
        $this->Teams= new Teams(new Users(1, 1));
    }

    public function testCreate(): void
    {
        $this->assertIsInt($this->Teams->create(new ContentParams('Test team')));
    }

    public function testRead(): void
    {
        $this->assertTrue(is_array($this->Teams->read(new ContentParams())));
    }

    public function testDestroy(): void
    {
        $id = $this->Teams->create(new ContentParams('Destroy me'));
        $this->Teams->setId($id);
        $this->Teams->destroy();
        // try to destroy a team with data
        $this->Teams->setId(1);
        $this->expectException(ImproperActionException::class);
        $this->Teams->destroy();
    }

    public function testGetAllStats(): void
    {
        $stats = $this->Teams->getAllStats();
        $this->assertTrue(is_array($stats));
        $this->assertEquals('0', $stats['totxpts']);
    }
}
