<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\UserParams;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Users;

class UserCreator
{
    // the user doing the request
    private Users $requester;

    public function __construct(private Users $target, private array $reqBody)
    {
        $this->requester = $target->requester;
        if ($this->requester->userData['is_admin'] !== 1) {
            throw new IllegalActionException('User creation is limited to Admins and Sysadmins only.');
        }
    }

    /**
     * Create a user from admin/sysadmin panels
     */
    public function create(): int
    {
        // only support creation of user in one team for now
        $team = $this->reqBody['team'];
        $teams = array('id' => $team);

        if ($this->requester->userData['is_sysadmin'] === 0) {
            $Config = Config::getConfig();
            // check for instance setting allowing/disallowing creation of users by admins
            if ($Config->configArr['admins_create_users'] === '0') {
                throw new IllegalActionException('Admin tried to create user but user creation is disabled for admins.');
            }
            // force using the team in which we are logged in if we are not sysadmin
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

    private function checkUsergroup(): int
    {
        $usergroup = Check::usergroup((int) $this->reqBody['usergroup']);
        if ($usergroup === 1 && $this->requester->userData['is_sysadmin'] !== 1) {
            throw new ImproperActionException('Only a sysadmin can promote another user to sysadmin.');
        }
        // a non sysadmin cannot demote a sysadmin
        if (isset($this->target->userData['is_sysadmin']) && $this->target->userData['is_sysadmin'] === 1 &&
            $usergroup !== 1 &&
            $this->requester->userData['is_sysadmin'] !== 1) {
            throw new ImproperActionException('Only a sysadmin can demote another sysadmin.');
        }
        return $usergroup;
    }
}
