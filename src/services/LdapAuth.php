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

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
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
    /** @var array $config */
    private $config;

    /** @var Connection $connection */
    private $connection;

    /** @var string $email */
    private $email = '';

    /** @var string $password */
    private $password = '';

    public function __construct(Config $config, string $email, string $password)
    {
        $c = $config->configArr;
        $this->config = array(
            'hosts' => array($c['ldap_host']),
            'port' => (int) $c['ldap_port'],
            'base_dn' => $c['ldap_base_dn'],
            'username' => $c['ldap_username'],
            'password' => Crypto::decrypt($c['ldap_password'], Key::loadFromAsciiSafeString(\SECRET_KEY)),
            'use_tls' => (bool) $c['ldap_use_tls'],
        );

        $this->connection = new Connection($this->config);
        $this->email = $email;
        $this->password = $password;
    }

    public function tryAuth(): AuthResponse
    {
        $query = $this->connection->query()->setDn($this->config['base_dn']);
        $record = $query->findbyOrFail('mail', $this->email);
        $cn = $record['cn'][0];
        if (!$this->connection->auth()->attempt('cn=' . $cn . ',' . $this->config['base_dn'], $this->password)) {
            throw new InvalidCredentialsException();
        }
        $AuthResponse = new AuthResponse();
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

        $AuthResponse->userid = (int) $Users->userData['userid'];
        $AuthResponse->mfaSecret = $Users->userData['mfa_secret'];
        $AuthResponse->setTeams();

        return $AuthResponse;
    }
}
