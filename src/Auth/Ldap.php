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

use Elabftw\Elabftw\Authentication;
use Elabftw\Enums\AuthMethod;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthenticatorInterface;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\ExistingUser;
use Elabftw\Models\Users\Users;
use Elabftw\Models\Users\ValidatedUser;
use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\Models\Entry;
use LdapRecord\Models\Model;
use LdapRecord\Query\ObjectNotFoundException;
use Override;
use SensitiveParameter;

use function _;
use function array_values;
use function explode;
use function is_array;
use function is_string;
use function trim;

/**
 * Authenticate a user against LDAP and resolve the corresponding local user.
 */
final class Ldap implements AuthenticatorInterface
{
    public function __construct(
        Connection $connection,
        private readonly Entry $entries,
        private readonly array $configArr,
        private readonly string $login,
        #[SensitiveParameter]
        private readonly string $password,
    ) {
        $connection->connect();
        Container::addConnection($connection);
    }

    #[Override]
    public function authenticate(): Authentication
    {
        $record = $this->getRecord();
        $this->verifyCredentials($record);

        $email = $this->getEmailFromRecord($record);
        $teamsFromLdap = $this->getTeamsFromRecord($record);

        try {
            $user = ExistingUser::fromEmail($email);
        } catch (ResourceNotFoundException) {
            $user = $this->createLocalUser(
                $record,
                $email,
                $teamsFromLdap,
            );
        }

        $this->synchronizeTeams(
            $user,
            $teamsFromLdap,
        );

        return new Authentication(
            $user->getUserid(),
            AuthMethod::Ldap,
        );
    }

    private function verifyCredentials(Model $record): void
    {
        $dn = $record->getDn();
        if ($dn === null) {
            throw new ImproperActionException('Error finding the dn!');
        }

        if (!Container::getConnection()->auth()->attempt($dn, $this->password)) {
            throw new InvalidCredentialsException();
        }
    }

    /**
     * @param list<mixed>|null $teamsFromLdap
     */
    private function createLocalUser(
        Model $record,
        string $email,
        ?array $teamsFromLdap,
    ): Users {
        if ($this->configArr['saml_user_default'] === '0') {
            $message = _('Could not find an existing user. Ask a Sysadmin to create your account.');

            if ($this->configArr['user_msg_need_local_account_created']) {
                $message = $this->configArr['user_msg_need_local_account_created'];
            }

            throw new ImproperActionException($message);
        }

        $firstname = $record[$this->configArr['ldap_firstname']][0] ?? 'Unknown';
        $lastname = $record[$this->configArr['ldap_lastname']][0] ?? 'Unknown';

        $teams = $teamsFromLdap ?? $this->getDefaultTeams();

        return ValidatedUser::fromExternal(
            $email,
            $teams,
            $firstname,
            $lastname,
            allowTeamCreation: $this->configArr['ldap_team_create'] === '1',
        );
    }

    /**
     * @return list<int>
     */
    private function getDefaultTeams(): array
    {
        $teamId = (int) $this->configArr['saml_team_default'];

        if ($teamId === 0) {
            throw new ImproperActionException('Could not find team ID to assign user!');
        }

        if ($teamId === -1) {
            // Authentication succeeded, but no local userid exists yet.
            // AuthenticatorInterface currently cannot represent this state.
            throw new ImproperActionException(
                'A team must be selected before creating this LDAP user.',
            );
        }

        return array($teamId);
    }

    /**
     * @return list<mixed>|null
     */
    private function getTeamsFromRecord(Model $record): ?array
    {
        $teams = $record[$this->configArr['ldap_team']];

        if ($teams === null) {
            return null;
        }

        if (is_string($teams)) {
            return array($teams);
        }

        if (!is_array($teams)) {
            throw new ImproperActionException(
                'Invalid team attribute returned by LDAP.',
            );
        }

        if (isset($teams[0]) && is_array($teams[0])) {
            $teams = $teams[0];
        }

        // LdapRecord may expose a synthetic "count" key.
        unset($teams['count']);

        return array_values($teams);
    }

    /**
     * @param list<mixed>|null $teamsFromLdap
     */
    private function synchronizeTeams(
        Users $user,
        ?array $teamsFromLdap,
    ): void {
        if (
            $this->configArr['ldap_sync_teams'] !== '1'
            || $teamsFromLdap === null
            || $teamsFromLdap === array()
        ) {
            return;
        }

        $teams = new Teams($user);
        $resolvedTeams = $teams->getTeamsFromIdOrNameOrOrgidArray(
            $teamsFromLdap,
            $this->configArr['ldap_team_create'] === '1',
        );

        $teams->synchronize(
            $user->getUserid(),
            $resolvedTeams,
        );
    }

    private function getRecord(): Model
    {
        $attributes = explode(',', $this->configArr['ldap_search_attr']);
        $this->entries->setDn($this->configArr['ldap_base_dn']);

        foreach ($attributes as $attribute) {
            try {
                return $this->entries::findbyOrFail(
                    trim($attribute),
                    $this->login,
                );
            } catch (ObjectNotFoundException) {
                continue;
            }
        }

        throw new InvalidCredentialsException();
    }

    private function getEmailFromRecord(Model $record): string
    {
        if ($this->configArr['ldap_search_attr'] === 'mail') {
            return $this->login;
        }

        $email = $record->getFirstAttribute(
            $this->configArr['ldap_email'],
        );

        if ($email === null) {
            throw new ImproperActionException(
                'Could not find the mail attribute from the LDAP record.',
            );
        }

        return $email;
    }
}
