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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

class Users2TeamsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Users2Teams $Users2Teams;

    protected function setUp(): void
    {
        $this->Users2Teams = new Users2Teams(new Users(1, 1));
    }

    public function testRmUserFromTeams(): void
    {
        $this->Users2Teams->addUserToTeams(4, array(3));
        $this->Users2Teams->rmUserFromTeams(4, array(3));
        $this->expectException(ImproperActionException::class);
        $this->Users2Teams->rmUserFromTeams(4, array(2));
    }

    public function testRmUserFromTeamButNotAdminInTeam(): void
    {
        // admin in team 2
        $admin2 = $this->getUserInTeam(team: 2, admin: 1);
        $Users2Teams = new Users2Teams($admin2);
        // user in bravo, adding them to alpha
        $user2 = $this->getUserInTeam(team: 2, admin: 0);
        $Users2Teams->addUserToTeams($user2->userid, array(1));
        $this->expectException(IllegalActionException::class);
        $Users2Teams->destroy(6, 1);
    }

    public function testRmAdminFromTeamAsSysadmin(): void
    {
        // admin in team 2
        $admin2 = $this->getUserInTeam(team: 2, admin: 1);
        // add tata to team alpha
        $this->Users2Teams->addUserToTeams($admin2->userid, array(1));
        // make tata Admin in Alpha
        $this->Users2Teams->patchUser2Team(array('team' => 1, 'target' => 'is_admin', 'content' => 1), $admin2->userid);
        // and remove tata from team alpha
        $this->Users2Teams->destroy($admin2->userid, 1);
    }

    public function testPatchUser2TeamGroup(): void
    {
        $params = array(
            'team' => 1,
            'target' => 'is_admin',
            'content' => 0,
        );
        $this->assertEquals(0, $this->Users2Teams->patchUser2Team($params, 2));
    }

    public function testPatchIsOwner(): void
    {
        $params = array(
            'team' => 1,
            'target' => 'is_owner',
            'content' => '1',
        );
        $this->assertEquals(1, $this->Users2Teams->patchUser2Team($params, 3));
        // now do it with a non sysadmin user
        $this->expectException(IllegalActionException::class);
        $Users2Teams = new Users2Teams(new Users(2, 1));
        $Users2Teams->patchUser2Team($params, 2);
    }

    public function testWasAdminAlready(): void
    {
        // create new user
        $newUser = (new Users(1, 1))->postAction(Action::Create, array(
            'team' => 2,
            'firstname' => 'was',
            'lastname' => 'admin',
            'email' => 'was@adminAlready.com',
        ));
        // promote to admin in team 2
        $this->Users2Teams->patchUser2Team(array(
            'team' => 2,
            'target' => 'is_admin',
            'content' => 1,
        ), $newUser);
        // promote to admin in team 1
        $this->assertEquals(1, $this->Users2Teams->patchUser2Team(array(
            'team' => 1,
            'target' => 'is_admin',
            'content' => 1,
        ), $newUser));
        // remove user again
        (new Users($newUser, 1))->destroy();
    }
}
