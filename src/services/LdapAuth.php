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
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use LdapRecord\Connection;

/**
 * LDAP auth service
 */
class LdapAuth implements AuthInterface
{
    /** @var Connection $connection */
    private $connection;

    /** @var string $email */
    private $email = '';

    /** @var string $password */
    private $password = '';

    /** @var AuthResponse $AuthResponse */
    private $AuthResponse;

    /** @var string $baseDn */
    private $baseDn;

    public function __construct(Connection $connection, string $baseDn, string $email, string $password)
    {
        $this->connection = $connection;
        $this->baseDn = $baseDn;
        $this->email = Filter::sanitize($email);
        $this->password = $password;
        $this->AuthResponse = new AuthResponse('ldap');
    }

    public function tryAuth(): AuthResponse
    {
        $query = $this->connection->query()->setDn($this->baseDn);
        $record = $query->findbyOrFail('mail', $this->email);
        $cn = $record['cn'][0];
        if (!$this->connection->auth()->attempt('cn=' . $cn . ',' . $this->baseDn, $this->password)) {
            throw new InvalidCredentialsException();
        }
        $Users = new Users();
        try {
            $Users->populateFromEmail($this->email);
        } catch (ResourceNotFoundException $e) {
            // the user doesn't exist yet in the db
            // GET FIRSTNAME AND LASTNAME
            // TODO add options in config to select which attribute is used
            $firstname = $record['givenname'][0];
            $lastname = $record['sn'][0];

            // GET TEAMS
            //$teams = $record['departmentNumber'];
            // TODO for now:
            $teams = array('Alpha');
            // CREATE USER (and force validation of user)
            $userid = $Users->create($this->email, $teams, $firstname, $lastname, '', null, true);
            $Users = new Users($userid);
        }

        $this->AuthResponse->userid = (int) $Users->userData['userid'];
        $this->AuthResponse->mfaSecret = $Users->userData['mfa_secret'];
        $this->AuthResponse->setTeams();

        return $this->AuthResponse;
    }
}
