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
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Users;

/**
 * Authenticate with server provided values
 */
class ExternalAuth implements AuthInterface
{
    /** @var App $App */
    private $App;

    /** @var AuthResponse $AuthResponse */
    private $AuthResponse;

    public function __construct(App $app)
    {
        $this->App = $app;
        $this->AuthResponse = new AuthResponse('external');
    }

    public function tryAuth(): AuthResponse
    {
        // use shorthands for lisibility
        $srv = $this->App->Request->server;
        $conf = $this->App->Config->configArr;

        $firstname = $srv->get($conf['extauth_firstname']) ?? '?';
        $lastname = $srv->get($conf['extauth_lastname']) ?? '?';
        $email = $srv->get($conf['extauth_email']);
        // try and get the team
        $teams = array($srv->get($conf['extauth_teams']));

        // no team found!
        if (empty($teams)) {
            // check for the default team
            $teamId = (int) $this->App->Config->configArr['saml_team_default'];
            // or throw error if sysadmin configured it like that
            if ($teamId === 0) {
                throw new ImproperActionException('Could not find team ID to assign user!');
            }
            $teams = array((string) $teamId);
        }

        // get userid
        $Users = new Users();
        try {
            $Users->populateFromEmail($email);
        } catch (ResourceNotFoundException $e) {
            // the user doesn't exist yet in the db
            // CREATE USER (and force validation of user)
            $userid = $Users->create($email, $teams, $firstname, $lastname, '', null, true);
            $Users->populateFromEmail($email);
            $this->App->Log->info('New user (' . $email . ') autocreated from external auth');
        }
        $userid = (int) $Users->userData['userid'];
        $UsersHelper = new UsersHelper();
        $availableTeams = $UsersHelper->getTeamsIdFromUserid($userid);
        $selectedTeam = (int) $availableTeams[0];
        $Users = new Users($userid, $selectedTeam);

        $this->AuthResponse->userid = $userid;
        $this->AuthResponse->selectedTeam = $Users->userData['team'];

        return $this->AuthResponse;
    }
}
