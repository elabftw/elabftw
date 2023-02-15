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
    public function __construct(private Users $requester, private array $reqBody)
    {
        if (!$this->requester->userData['is_admin']) {
            throw new IllegalActionException('User creation is limited to Admins and Sysadmins only.');
        }
    }

    /**
     * Create a user from admin/sysadmin panels
     */
    public function create(): int
    {
        // only support creation of user in one team for now
        $team = $this->reqBody['team'] ?? $this->requester->userData['team'];
        $teams = array('id' => $team);

        if (!$this->requester->userData['is_sysadmin']) {
            $Config = Config::getConfig();
            // check for instance setting allowing/disallowing creation of users by admins
            if ($Config->configArr['admins_create_users'] === '0') {
                throw new IllegalActionException('Admin tried to create user but user creation is disabled for admins.');
            }
            // force using the team in which we are logged in if we are not sysadmin
            $teams = array('id' => $this->requester->userData['team']);
        }
        $validUntil = $this->reqBody['valid_until'] ?? null;
        $usergroup = $this->checkUsergroup();

        return (new Users())->createOne(
            (new UserParams('email', $this->reqBody['email']))->getContent(),
            $teams,
            (new UserParams('firstname', $this->reqBody['firstname']))->getContent(),
            (new UserParams('lastname', $this->reqBody['lastname']))->getContent(),
            // password is never set by admin/sysadmin
            '',
            // isAdmin
            $usergroup === 2,
            // isSysadmin
            $usergroup === 1,
            // automatically validate user
            true,
            // don't alert admin
            false,
            $validUntil,
        );
    }

    /**
     * Check to prevent a non sysadmin to create a sysadmin user
     */
    private function checkUsergroup(): int
    {
        $usergroup = Check::usergroup((int) ($this->reqBody['usergroup'] ?? 4));
        if ($usergroup === 1 && !$this->requester->userData['is_sysadmin']) {
            throw new ImproperActionException('Only a sysadmin can promote another user to sysadmin.');
        }
        return $usergroup;
    }
}
