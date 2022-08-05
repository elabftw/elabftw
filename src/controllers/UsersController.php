<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Elabftw\UserParams;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Services\Check;

class UsersController
{
    public function __construct(private Users $requester, private Users $target, private array $reqBody)
    {
        // a normal user can only access their own user
        // you need to be at least admin to access another user
        // TODO when we implement the @firstname autocompletion for notification, normal users will need to access a stripped down version of user list
        // maybe it'll be a custom function instead of normal get filtered
        if ($requester->userData['is_admin'] !== 1 && $this->target->userid !== $this->target->userData['userid']) {
            throw new IllegalActionException('This endpoint requires admin privileges to access other users.');
        }
        // check we edit user of our team, unless we are sysadmin and we can access it
        if ($this->target->userid !== null && !$this->requester->isAdminOf($this->target->userid)) {
            throw new IllegalActionException('User tried to access user from other team.');
        }
    }

    /**
     * Handle an action
     */
    public function handleAction(): array
    {
        return match ($this->reqBody['action']) {
            'archive' => $this->archive(),
            'validate' => $this->target->validate(),
            // if no specific action is set, create a user
            default => throw new ImproperActionException('Invalid action provided to user controller.'),
        };
    }

    /**
     * Create a user from admin/sysadmin panels
     */
    public function create(): int
    {
        // only support creation of user in one team for now
        $team = $this->reqBody['team'];
        $teams = array('id' => $team);

        if ($this->requester->userData['is_sysadmin'] !== 1) {
            $Config = Config::getConfig();
            // check for instance setting allowing/disallowing creation of users by admins
            if ($Config->configArr['admins_create_users'] === '0') {
                throw new IllegalActionException('Admin tried to create user but user creation is disabled for admins.');
            }
            // check if we are admin of the correct team
            $Teams = new Teams($this->requester);
            if ($Teams->hasCommonTeamWithCurrent($this->requester->userid, $team) === false) {
                throw new IllegalActionException('Admin tried to create user in a team where they are not admin.');
            }
        }
        // check if we are admin the team is ours
        // a sysadmin is free to use any team
        if ($this->requester->userData['is_sysadmin'] === 0) {
            // note: from REST API call the team is not set!! TODO FIXME
            // force using our own team
            // make a isAdminOfTeam()
            $teams = array('id' => $this->requester->userData['team']);
        }
        return $this->target->createOne(
            (new UserParams($this->reqBody['email'], 'email'))->getContent(),
            $teams,
            (new UserParams($this->reqBody['firstname'], 'firstname'))->getContent(),
            (new UserParams($this->reqBody['lastname'], 'lastname'))->getContent(),
            // password is never set by admin/sysadmin
            '',
            $this->checkUsergroup(),
            // automatically validate user
            true,
            // don't alert admin
            false,
        );
    }

    private function archive(): array
    {
        if ($this->target->userData['validated'] === 0) {
            throw new ImproperActionException('You are trying to archive an unvalidated user. Maybe you want to delete the account?');
        }

        $this->target->toggleArchive();

        // if we are archiving a user, also lock all experiments
        if ($this->target->userData['archived'] === 0) {
            $this->target->lockExperiments();
        }
        return $this->target->readOne();
    }

    private function checkUsergroup(): int
    {
        $usergroup = Check::usergroup((int) $this->reqBody['usergroup']);
        if ($usergroup === 1 && $this->requester->userData['is_sysadmin'] !== 1) {
            throw new ImproperActionException('Only a sysadmin can promote another user to sysadmin.');
        }
        // a non sysadmin cannot demote a sysadmin
        if ($this->target->userData['is_sysadmin'] && $usergroup !== 1 && $this->requester->userData['is_sysadmin'] !== 1) {
            throw new ImproperActionException('Only a sysadmin can demote another sysadmin.');
        }
        return $usergroup;
    }
}
