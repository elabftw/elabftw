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
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\ExistingUser;
use Elabftw\Models\ValidatedUser;
use function is_array;
use LdapRecord\Connection;
use LdapRecord\Query\ObjectNotFoundException;

/**
 * LDAP auth service
 */
class LdapAuth implements AuthInterface
{
    // the login string, email or uid or else
    private string $login = '';

    private AuthResponse $AuthResponse;

    public function __construct(private Connection $connection, private array $configArr, string $login, private string $password)
    {
        $this->login = Filter::sanitize($login);
        $this->AuthResponse = new AuthResponse();
    }

    public function tryAuth(): AuthResponse
    {
        $query = $this->connection->query()->setDn($this->configArr['ldap_base_dn']);
        try {
            /** @var array $record */
            $record = $query->findbyOrFail($this->configArr['ldap_search_attr'], $this->login);
        } catch (ObjectNotFoundException) {
            throw new InvalidCredentialsException(0);
        }
        $dn = $record['distinguishedname'] ?? $record['dn'];
        // sometimes it might be an array, make sure we give a string to auth
        if (is_array($dn)) {
            $dn = $dn[0];
        }
        if (!$this->connection->auth()->attempt($dn, $this->password)) {
            throw new InvalidCredentialsException(0);
        }

        // this->login can also be uid
        $email = $this->getEmailFromRecord($record);
        try {
            $Users = ExistingUser::fromEmail($email);
        } catch (ResourceNotFoundException) {
            // the user doesn't exist yet in the db
            // what do we do? Lookup the config setting for that case
            if ($this->configArr['saml_user_default'] === '0') {
                throw new ImproperActionException('Could not find an existing user. Ask a Sysadmin to create your account.');
            }
            // GET FIRSTNAME AND LASTNAME
            $firstname = $record[$this->configArr['ldap_firstname']][0] ?? 'Unknown';
            $lastname = $record[$this->configArr['ldap_lastname']][0] ?? 'Unknown';

            // GET TEAMS
            $teamFromLdap = $record[$this->configArr['ldap_team']];
            // the attribute is not found
            if ($teamFromLdap === null) {
                // we directly get the id from the stored config
                $teamId = (int) $this->configArr['saml_team_default'];
                if ($teamId === 0) {
                    throw new ImproperActionException('Could not find team ID to assign user!');
                }
                // this setting is when we want to allow the user to make team selection
                if ($teamId === -1) {
                    $this->AuthResponse->userid = 0;
                    $this->AuthResponse->initTeamRequired = true;
                    $this->AuthResponse->initTeamUserInfo = array(
                        'email' => $email,
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                    );
                    return $this->AuthResponse;
                }
                $teamFromLdap = array($teamId);
            // it is found and it is a string
            } elseif (is_string($teamFromLdap)) {
                $teamFromLdap = array($teamFromLdap);
            // it is found and it is an array
            } elseif (is_array($teamFromLdap)) {
                if (is_array($teamFromLdap[0])) {
                    // go one level deeper
                    $teamFromLdap = $teamFromLdap[0];
                }
            }
            // ldap might return a "count" key, so we remove it or it will be interpreted as a team ID
            unset($teamFromLdap['count']);
            // CREATE USER (and force validation of user)
            $Users = ValidatedUser::fromExternal($email, $teamFromLdap, $firstname, $lastname);
        }

        $this->AuthResponse->userid = (int) $Users->userData['userid'];
        $this->AuthResponse->mfaSecret = $Users->userData['mfa_secret'];
        $this->AuthResponse->setTeams();

        return $this->AuthResponse;
    }

    private function getEmailFromRecord(array $record): string
    {
        // if the login input is the email, we have it already
        if ($this->configArr['ldap_search_attr'] === 'mail') {
            return $this->login;
        }
        $email = $record[$this->configArr['ldap_email']];
        if (is_array($email)) {
            return $email[0];
        }
        return $email;
    }
}
