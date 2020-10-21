<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\AuthResponse;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Users;

/**
 * Authenticate with server provided values
 */
class ExternalAuth implements AuthInterface
{
    /** @var App $App */
    private $App;

    public function __construct(App $app)
    {
        $this->App = $app;
    }

    public function tryAuth(): AuthResponse
    {
        // use shorthands for lisibility
        $srv = $this->App->Request->server;
        $conf = $this->App->Config->configArr;

        $firstname = $srv->get($conf['extauth_firstname']);
        $lastname = $srv->get($conf['extauth_lastname']);
        $email = $srv->get($conf['extauth_email']);
        // try and get the team
        $teamId = $srv->get($conf['extauth_teams']);

        // no team found!
        if (empty($teamId)) {
            // check for the default team
            $teamId = (int) $this->App->Config->configArr['saml_team_default'];
            // or throw error if sysadmin configured it like that
            if ($teamId === 0) {
                throw new ImproperActionException('Could not find team ID to assign user!');
            }
        }
        $teams = array((string) $teamId);
        $Users = new Users();
        $Users->populateFromEmail($email);
        $userid = (int) $Users->userData['userid'];
        if ($userid === 0) {
            $this->App->Users->create($email, $teams, $firstname, $lastname, '');
            $this->App->Log->info('New user ' . $email . ' autocreated from external auth');
        }

        // add this to the session so for logout we know we need to hit the logout_url from config to logout from external server too
        // TODO this should be a type param of AuthResponse and the login->populate session should set it accordingly
        // use auth_by with values external, cookie, etc...
        $this->App->Session->set('is_ext_auth', 1);

        $AuthResponse = new AuthResponse();
        $AuthResponse->userid = $userid;
        $AuthResponse->selectedTeam = $teamId;
        return $AuthResponse;
    }
}
