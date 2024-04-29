<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;

class TeamGroupsTest extends \PHPUnit\Framework\TestCase
{
    private TeamGroups $TeamGroups;

    protected function setUp(): void
    {
        $this->TeamGroups = new TeamGroups(new Users(1, 1));
    }

    public function testCreate(): void
    {
        $this->assertIsInt($this->TeamGroups->postAction(Action::Create, array('name' => 'Group Name')));
    }

    public function testGetPage(): void
    {
        $this->assertEquals('api/v2/teams/1/teamgroups/', $this->TeamGroups->getPage());
    }

    public function testReadOne(): void
    {
        $id = $this->TeamGroups->postAction(Action::Create, array('name' => 'Group Name'));
        $this->TeamGroups->setId($id);
        $this->assertEquals('Group Name', $this->TeamGroups->readOne()['name']);
    }

    public function testUpdate(): void
    {
        $this->TeamGroups->setId(1);
        $this->assertIsArray($this->TeamGroups->patch(Action::Update, array('name' => 'New Name')));
    }

    public function testUpdateMember(): void
    {
        $this->TeamGroups->setId(1);
        $this->TeamGroups->patch(Action::Update, array('userid' => 1, 'how' => Action::Add->value));
        $this->assertTrue($this->TeamGroups->isInTeamGroup(1, 1));
        $this->TeamGroups->patch(Action::Update, array('userid' => 1, 'how' => Action::Unreference->value));
        $this->assertFalse($this->TeamGroups->isInTeamGroup(1, 1));
        $this->expectException(IllegalActionException::class);
        $this->TeamGroups->patch(Action::Update, array('userid' => 1, 'how' => 'invalidhow'));
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->TeamGroups->readAll());
        $this->assertIsArray($this->TeamGroups->readAllSimple());
        $this->assertIsArray($this->TeamGroups->readAllGlobal());
    }

    public function testDestroy(): void
    {
        $this->TeamGroups->setId(1);
        $this->assertTrue($this->TeamGroups->destroy());
    }
}
