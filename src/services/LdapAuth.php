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
            'port' => $c['ldap_port'],
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
        $AuthResponse->isAuthenticated = true;
        $UsersHelper = new UsersHelper();
        $Users = new Users();
        $Users->populateFromEmail($this->email);

        $AuthResponse->userid = (int) $Users->userData['userid'];
        $AuthResponse->mfaSecret = $Users->userData['mfa_secret'];
        $AuthResponse->selectableTeams = $UsersHelper->getTeamsFromUserid($AuthResponse->userid);

        // if the user only has access to one team, use this one directly
        if (count($AuthResponse->selectableTeams) === 1) {
            $AuthResponse->selectedTeam = (int) $AuthResponse->selectableTeams[0]['id'];
        }

        return $AuthResponse;
    }
}
