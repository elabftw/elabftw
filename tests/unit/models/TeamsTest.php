<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;

class TeamsTest extends \PHPUnit\Framework\TestCase
{
    private Teams $Teams;

    protected function setUp(): void
    {
        $this->Teams= new Teams(new Users(1, 1));
    }

    public function testGetPage(): void
    {
        $this->assertEquals('api/v2/teams/', $this->Teams->getPage());
    }

    public function testCreate(): void
    {
        $this->assertIsInt($this->Teams->postAction(Action::Create, array('name' => 'Test team')));
    }

    public function testImproperAction(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Teams->patch(Action::Timestamp, array());
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->Teams->readOne());
        $this->assertIsArray($this->Teams->readAll());
    }

    public function testDestroy(): void
    {
        $id = $this->Teams->postAction(Action::Create, array('name' => 'Destroy me'));
        $this->Teams->setId($id);
        $this->assertTrue($this->Teams->destroy());
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
