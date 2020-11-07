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

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Users;
use Monolog\Logger;

/**
 * Authenticate with server provided values
 */
class ExternalAuth implements AuthInterface
{
    /** @var AuthResponse $AuthResponse */
    private $AuthResponse;

    /** @var array $configArr */
    private $configArr;

    /** @var Logger $log */
    private $log;

    /** @var array $serverParams */
    private $serverParams;

    public function __construct(array $configArr, array $serverParams, Logger $log)
    {
        $this->AuthResponse = new AuthResponse('external');
        $this->configArr = $configArr;
        $this->serverParams = $serverParams;
        $this->log = $log;
    }

    public function tryAuth(): AuthResponse
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
        $Users = new Users();
        try {
            $Users->populateFromEmail($email);
        } catch (ResourceNotFoundException $e) {
            // the user doesn't exist yet in the db
            // CREATE USER (and force validation of user)
            $Users->create($email, $teams, $firstname, $lastname, '', null, true);
            $Users->populateFromEmail($email);
            $this->log->info('New user (' . $email . ') autocreated from external auth');
        }
        $this->AuthResponse->userid = (int) $Users->userData['userid'];
        $this->AuthResponse->setTeams();

        return $this->AuthResponse;
    }
}
