<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

class StatusTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private ExperimentsStatus $Status;

    protected function setUp(): void
    {
        $this->Status = new ExperimentsStatus(new Teams(new Users(1, 1), 1), 1);
    }

    public function testCreate(): void
    {
        $new = $this->Status->postAction(Action::Create, array('title' => 'New status', 'color' => '#29AEB9', 'is_default' => 1));
        $this->assertIsInt($new);
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->Status->readOne());
    }

    public function testGetApiPath(): void
    {
        $this->assertIsString($this->Status->getApiPath());
    }

    public function testUpdate(): void
    {
        $id = $this->Status->postAction(Action::Create, array('title' => 'Yop', 'color' => '#29AEB9', 'is_private' => 0));
        $Status = new ExperimentsStatus(new Teams(new Users(1, 1), 1), $id);
        $status = $Status->patch(Action::Update, array('title' => 'Updated', 'color' => '#121212'));
        $this->assertEquals('Updated', $status['title']);
        $this->assertEquals('121212', $status['color']);
        $this->assertEquals(0, $status['is_default']);
        $status = $Status->patch(Action::Update, array('title' => 'Updated', 'color' => '#121212', 'is_default' => 1));
        $this->assertEquals(1, $status['is_default']);
    }

    public function testDestroy(): void
    {
        $id = $this->Status->postAction(Action::Create, array('title' => 'Yop', 'color' => '#29AEB9'));
        $Status = new ExperimentsStatus(new Teams(new Users(1, 1), 1), $id);
        $this->assertTrue($Status->destroy());
        $this->assertTrue($this->Status->destroy());
    }

    public function testCannotReadStatusFromAnotherTeamThroughCurrentTeam(): void
    {
        $id = $this->Status->postAction(Action::Create, array('title' => 'Yop', 'color' => '#29AEB9'));
        $otherStatus = new ExperimentsStatus(new Teams($this->getRandomUserInTeam(2)), $id);
        $this->expectException(ResourceNotFoundException::class);
        $otherStatus->readOne();
    }

    public function testCannotReadOtherTeam(): void
    {
        $this->expectException(ImproperActionException::class);

        $Team = new Teams($this->getRandomUserInTeam(2), 1);
        $Team->readOne();
    }

    public function testUpdateNonAccessibleStatusThroughCurrentTeam(): void
    {
        $id = $this->Status->postAction(Action::Create, array('title' => 'Yop', 'color' => '#29AEB9'));
        $otherStatus = new ExperimentsStatus(new Teams($this->getRandomUserInTeam(2)), $id);
        $this->expectException(ResourceNotFoundException::class);
        $otherStatus->patch(Action::Create, array('title' => 'Coucou'));
    }
}
