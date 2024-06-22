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
use Elabftw\Enums\Usergroup;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;

class Users2TeamsTest extends \PHPUnit\Framework\TestCase
{
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

    public function testPatchUser2TeamGroup(): void
    {
        $params = array(
            'userid' => 2,
            'team' => 1,
            'target' => 'group',
            'content' => Usergroup::User->value,
        );
        $this->assertEquals(4, $this->Users2Teams->patchUser2Team($params));
    }

    public function testPatchIsOwner(): void
    {
        $params = array(
            'userid' => 3,
            'team' => 1,
            'target' => 'is_owner',
            'content' => '1',
        );
        $this->assertEquals(1, $this->Users2Teams->patchUser2Team($params));
        // now do it with a non sysadmin user
        $this->expectException(IllegalActionException::class);
        $Users2Teams = new Users2Teams(new Users(2, 1));
        $Users2Teams->patchUser2Team($params);
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
            'userid' => $newUser,
            'team' => 2,
            'target' => 'group',
            'content' => Usergroup::Admin->value,
        ));
        // promote to admin in team 1
        $this->assertEquals(2, $this->Users2Teams->patchUser2Team(array(
            'userid' => $newUser,
            'team' => 1,
            'target' => 'group',
            'content' => Usergroup::Admin->value,
        )));
        // remove user again
        (new Users($newUser, 1))->destroy();
    }
}
