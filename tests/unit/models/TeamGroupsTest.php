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
use Elabftw\Models\Users\Users;

use function trim;

class TeamGroupsTest extends \PHPUnit\Framework\TestCase
{
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

    public function testReadAllDisplaysFullNames(): void
    {
        $groups = $this->TeamGroups->readAll();
        $this->assertIsArray($groups, 'readAll() should return an array of groups.');
        foreach ($groups as $group) {
            $this->assertArrayHasKey('users', $group, 'Each group should have a "users" key.');
            $this->assertIsArray($group['users'], 'Group "users" should be an array.');
            // for each user entry, ensure 'userid' and 'fullname' exist
            foreach ($group['users'] as $user) {
                $this->assertArrayHasKey('userid', $user, 'Each user should have a "userid"');
                $this->assertArrayHasKey('fullname', $user, 'Each user should have a "fullname" key');
            }
            // ensure number of fullnames matches number of userids
            $userIds = array_column($group['users'], 'userid');
            $fullnames = array_column($group['users'], 'fullname');
            $this->assertSameSize(
                $userIds,
                $fullnames,
                sprintf(
                    'group "%s" has mismatched count of userids (%d) and fullnames (%d).',
                    $group['name'],
                    count($userIds),
                    count($fullnames)
                )
            );
            foreach ($fullnames as $fullname) {
                $this->assertNotEmpty(trim($fullname), 'User fullname should not be empty.');
            }
        }
    }

    public function testDestroy(): void
    {
        $this->TeamGroups->setId(1);
        $this->assertTrue($this->TeamGroups->destroy());
    }
}
