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
use Elabftw\Enums\Scope;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

use function array_column;

class TeamGroupsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private TeamGroups $TeamGroups;

    private Users $Users;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->TeamGroups = new TeamGroups($this->Users);
    }

    public function testCreate(): void
    {
        $this->assertIsInt($this->TeamGroups->postAction(Action::Create, array('name' => 'Group Name')));
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/teams/1/teamgroups/', $this->TeamGroups->getApiPath());
    }

    public function testReadOne(): void
    {
        $id = $this->TeamGroups->postAction(Action::Create, array('name' => 'Group Name'));
        $this->TeamGroups->setId($id);
        $this->assertEquals('Group Name', $this->TeamGroups->readOne()['name']);
    }

    public function testReadNamesFromId(): void
    {
        $id = $this->TeamGroups->postAction(Action::Create, array('name' => 'yep'));
        $id2 = $this->TeamGroups->postAction(Action::Create, array('name' => 'yop'));
        $res = $this->TeamGroups->readNamesFromIds(array($id, $id2));
        $this->assertEquals('yep', $res[0]['name']);
        $this->assertEquals('yop', $res[1]['name']);
    }

    public function testGroupsOfHiddenTeamsAreNotDisclosed(): void
    {
        $teamId = new Teams($this->Users)->postAction(Action::Create, array('name' => 'Hidden team with a group'));
        $HiddenTeam = new Teams($this->Users, $teamId);
        $HiddenTeam->patch(Action::Update, array('visible' => 0));
        // create a group in that hidden team
        $groupId = new TeamGroups(new Users(1, $teamId))->create('Secret group');

        try {
            $user = $this->getUserInTeam(2);
            $TeamGroups = new TeamGroups($user);
            $this->assertEmpty($TeamGroups->readNamesFromIds(array($groupId)));
            $this->assertNotContains($groupId, array_column($TeamGroups->readAllEverything(), 'id'));
            $TeamGroups->setId($groupId);
            try {
                $TeamGroups->readOne();
                $this->fail('A group of a hidden team was read by a user outside of that team.');
            } catch (ResourceNotFoundException) {
                $this->addToAssertionCount(1);
            }

            // Sysadmin still sees it
            $this->assertSame('Secret group', $this->TeamGroups->readNamesFromIds(array($groupId))[0]['name']);
            $this->assertContains($groupId, array_column($this->TeamGroups->readAllEverything(), 'id'));

            // and so does a member of that group
            new TeamGroups($this->Users, $groupId)->updateMember(array('how' => Action::Add->value, 'userid' => $user->userid));
            $this->assertSame('Secret group', $TeamGroups->readNamesFromIds(array($groupId))[0]['name']);
            $this->assertContains($groupId, array_column($TeamGroups->readAllEverything(), 'id'));
            $this->assertSame($groupId, $TeamGroups->readOne()['id']);
        } finally {
            // the group is removed by the foreign key
            $HiddenTeam->destroy();
        }
    }

    public function testReadScopedTeamgroups(): void
    {
        $this->Users->userData['scope_teamgroups'] = Scope::User->value;
        $this->assertIsArray($this->TeamGroups->readScopedTeamgroups());
        $this->Users->userData['scope_teamgroups'] = Scope::Team->value;
        $this->assertIsArray($this->TeamGroups->readScopedTeamgroups());
        $this->Users->userData['scope_teamgroups'] = Scope::Everything->value;
        $this->assertIsArray($this->TeamGroups->readScopedTeamgroups());
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
        $this->expectException(ImproperActionException::class);
        $this->TeamGroups->patch(Action::Update, array('userid' => 1, 'how' => 'invalidhow'));
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->TeamGroups->readAll());
        $this->assertIsArray($this->TeamGroups->readAllUser());
        $this->assertIsArray($this->TeamGroups->readAllTeam());
        $this->assertIsArray($this->TeamGroups->readAllEverything());
    }

    public function testDestroy(): void
    {
        $this->TeamGroups->setId(1);
        $this->assertTrue($this->TeamGroups->destroy());
    }
}
