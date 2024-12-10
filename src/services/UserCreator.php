<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\UserParams;
use Elabftw\Enums\Usergroup;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Users;

class UserCreator
{
    public function __construct(private Users $requester, private array $reqBody) {}

    /**
     * Create a user from admin/sysadmin panels
     */
    public function create(): int
    {
        // only support creation of user in one team for now
        $team = $this->reqBody['team'] ?? $this->requester->userData['team'];
        $teams = array('id' => $team);

        if ($this->requester->userData['is_sysadmin'] === 0) {
            $Config = Config::getConfig();
            // check for instance setting allowing/disallowing creation of users by admins
            if ($Config->configArr['admins_create_users'] === '0' && $Config->configArr['admins_create_users_remote_dir'] === '0') {
                throw new IllegalActionException('Admin tried to create user but user creation is disabled for admins.');
            }
            // force using the team in which we are logged in if we are not sysadmin
            $teams = array('id' => $this->requester->userData['team']);
            if (!$this->requester->isAdmin) {
                throw new IllegalActionException('User tried to create user and this is not allowed');
            }
        }
        $validUntil = $this->reqBody['valid_until'] ?? null;
        $orgid = null;
        if (isset($this->reqBody['orgid'])) {
            $orgid = (new UserParams('orgid', $this->reqBody['orgid']))->getContent();
        }
        return (new Users(null, null, $this->requester))->createOne(
            (new UserParams('email', $this->reqBody['email']))->getContent(),
            $teams,
            (new UserParams('firstname', $this->reqBody['firstname']))->getContent(),
            (new UserParams('lastname', $this->reqBody['lastname']))->getContent(),
            // password is never set by admin/sysadmin
            '',
            Check::usergroup($this->requester, Usergroup::from((int) ($this->reqBody['usergroup'] ?? Usergroup::User->value))),
            // automatically validate user
            true,
            // don't alert admin
            false,
            $validUntil,
            $orgid,
        );
    }
}
