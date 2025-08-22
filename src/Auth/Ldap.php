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
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\AuthResponseInterface;
use Elabftw\Models\Users\ExistingUser;
use Elabftw\Models\Users\ValidatedUser;
use Elabftw\Services\UsersHelper;
use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\Models\Entry;
use LdapRecord\Models\Model;
use LdapRecord\Query\ObjectNotFoundException;
use SensitiveParameter;
use Override;

use function explode;
use function is_array;

/**
 * LDAP auth service
 */
final class Ldap implements AuthInterface
{
    public function __construct(Connection $connection, private Entry $entries, private array $configArr, private string $login, #[SensitiveParameter] private string $password)
    {
        // add connection to the Container https://ldaprecord.com/docs/core/v3/connections/#container
        $connection->connect();
        Container::addConnection($connection);
    }

    #[Override]
    public function tryAuth(): AuthResponseInterface
    {
        $record = $this->getRecord();
        $dn = $record->getDn();
        if ($dn === null) {
            throw new ImproperActionException('Error finding the dn!');
        }
        if (!Container::getConnection()->auth()->attempt($dn, $this->password)) {
            throw new InvalidCredentialsException(0);
        }

        // this->login can also be uid
        $email = $this->getEmailFromRecord($record);
        $AuthResponse = new AuthResponse();
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
                    $AuthResponse->setInitTeamRequired(true);
                    $AuthResponse->setInitTeamInfo(array(
                        'email' => $email,
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                    ));
                    return $AuthResponse;
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
            $Users = ValidatedUser::fromExternal($email, $teamFromLdap, $firstname, $lastname, allowTeamCreation: (bool) $this->configArr['saml_team_create']);
        }

        return $AuthResponse->setAuthenticatedUserid($Users->userData['userid'])
            ->setTeams(new UsersHelper($Users->userData['userid']));
    }

    // split the search attributes and search the user with them
    private function getRecord(): Model
    {
        $attributes = explode(',', $this->configArr['ldap_search_attr']);
        $this->entries->setDn($this->configArr['ldap_base_dn']);
        foreach ($attributes as $attribute) {
            try {
                return $this->entries::findbyOrFail(trim($attribute), $this->login);
            } catch (ObjectNotFoundException) {
                continue;
            }
        }
        throw new InvalidCredentialsException(0);
    }

    private function getEmailFromRecord(Model $record): string
    {
        // if the login input is the email, we have it already
        if ($this->configArr['ldap_search_attr'] === 'mail') {
            return $this->login;
        }
        $email = $record->getFirstAttribute($this->configArr['ldap_email']);
        if ($email === null) {
            throw new ImproperActionException('Could not find the mail attribute from the LDAP record.');
        }
        return $email;
    }
}
