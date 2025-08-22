<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\AuthResponseInterface;
use Elabftw\Models\Users\ExistingUser;
use Elabftw\Models\Users\ValidatedUser;
use Elabftw\Services\UsersHelper;
use Override;

/**
 * Authenticate with server provided values
 */
final class External implements AuthInterface
{
    public function __construct(private array $configArr, private array $serverParams) {}

    #[Override]
    public function tryAuth(): AuthResponseInterface
    {
        $firstname = $this->serverParams[$this->configArr['extauth_firstname']] ?? '?';
        $lastname = $this->serverParams[$this->configArr['extauth_lastname']] ?? '?';
        $email = $this->serverParams[$this->configArr['extauth_email']] ?? '?';
        // try and get the team
        $teams = array($this->serverParams[$this->configArr['extauth_teams']]);

        // no team found!
        if (empty($teams[0])) {
            $defaultTeam = (int) $this->configArr['saml_team_default'];
            // or throw error if sysadmin configured it like that
            if ($defaultTeam === 0) {
                throw new ImproperActionException('Could not find team ID to assign user!');
            }
            $teams = array((string) $defaultTeam);
        }

        // get userid
        try {
            $Users = ExistingUser::fromEmail($email);
        } catch (ResourceNotFoundException) {
            // the user doesn't exist yet in the db
            // what do we do? Lookup the config setting for that case
            if ($this->configArr['saml_user_default'] === '0') {
                $msg = _('Could not find an existing user. Ask a Sysadmin to create your account.');
                if ($this->configArr['user_msg_need_local_account_created']) {
                    $msg = $this->configArr['user_msg_need_local_account_created'];
                }
                throw new ImproperActionException($msg);
            }
            // CREATE USER (and force validation of user)
            $Users = ValidatedUser::fromExternal($email, $teams, $firstname, $lastname);
        }
        return new AuthResponse()
            ->setAuthenticatedUserid($Users->userData['userid'])
            ->setTeams(new UsersHelper($Users->userData['userid']));
    }
}
