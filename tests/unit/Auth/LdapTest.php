<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use Elabftw\Elabftw\Authentication;
use Elabftw\Enums\AuthMethod;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Models\Users\ExistingUser;
use LdapRecord\Container;
use LdapRecord\Models\Entry;
use LdapRecord\Testing\ConnectionFake;
use LdapRecord\Testing\LdapFake;
use PHPUnit\Framework\TestCase;

use function array_replace;

class LdapTest extends TestCase
{
    private const string BASE_DN = 'cn=User,dc=local,dc=com';

    private const string DN = 'cn=Toto,cn=User,dc=local,dc=com';

    private const string EMAIL = 'toto@yopmail.com';

    private const string PASSWORD = 'totototototo';

    private array $configArr;

    private array $ldapConfig;

    private LdapFake $ldapFake;

    protected function setUp(): void
    {
        $this->configArr = array(
            'ldap_base_dn' => self::BASE_DN,
            'ldap_email' => 'mail',
            'ldap_search_attr' => 'mail',
            'ldap_firstname' => 'givenname',
            'ldap_lastname' => 'sn',
            'ldap_team' => 'ou',
            'ldap_sync_teams' => '0',
            'ldap_team_create' => '0',
            'saml_user_default' => '0',
            'saml_team_default' => '0',
            'user_msg_need_local_account_created' => '',
        );
        $this->ldapConfig = array(
            'hosts' => array('127.0.0.1'),
            'port' => 389,
            'base_dn' => self::BASE_DN,
            'username' => 'Toto',
            'password' => self::PASSWORD,
            'use_tls' => false,
        );
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->ldapFake)) {
                $this->ldapFake->assertMinimumExpectationCounts();
            }
        } finally {
            Container::getInstance()->getConnectionManager()->flush();
            parent::tearDown();
        }
    }

    public function testAuthenticateExistingUser(): void
    {
        $authentication = $this->createLdap(array($this->getSearchResult()))->authenticate();

        self::assertInstanceOf(Authentication::class, $authentication);
        self::assertSame(ExistingUser::fromEmail(self::EMAIL)->getUserid(), $authentication->userid);
        self::assertSame(AuthMethod::Ldap, $authentication->method);
    }

    public function testAuthenticateRejectsUnknownLdapUser(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->createLdap(array(array()), userDn: null)->authenticate();
    }

    public function testAuthenticateRejectsInvalidPassword(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->createLdap(array($this->getSearchResult()), password: 'wrong-password', credentialsValid: false)->authenticate();
    }

    public function testAuthenticateTriesAllConfiguredSearchAttributes(): void
    {
        $authentication = $this->createLdap(
            array(array(), $this->getSearchResult()),
            config: array('ldap_search_attr' => 'uid, mail'),
        )->authenticate();

        self::assertInstanceOf(Authentication::class, $authentication);
        self::assertSame(AuthMethod::Ldap, $authentication->method);
    }

    public function testAuthenticateRejectsRecordWithoutEmail(): void
    {
        $searchResult = $this->getSearchResult();
        unset($searchResult[0]['mail']);

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Could not find the mail attribute from the LDAP record.');
        $this->createLdap(array($searchResult), login: 'toto', config: array('ldap_search_attr' => 'uid'))->authenticate();
    }

    public function testAuthenticateRejectsUnknownLocalUserWhenCreationIsDisabled(): void
    {
        $email = 'ldap-user-not-created@example.com';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('LDAP account creation disabled');
        $this->createLdap(
            array($this->getSearchResult($email)),
            login: 'ldap-user-not-created',
            config: array(
                'ldap_search_attr' => 'uid',
                'user_msg_need_local_account_created' => 'LDAP account creation disabled',
            ),
        )->authenticate();
    }

    public function testAuthenticateCanRequireInitialTeamSelection(): void
    {
        $email = 'ldap-team-selection@example.com';
        $result = $this->createLdap(
            array($this->getSearchResult($email, withTeam: false)),
            login: 'ldap-team-selection',
            config: array(
                'ldap_search_attr' => 'uid',
                'saml_user_default' => '1',
                'saml_team_default' => '-1',
            ),
        )->authenticate();

        self::assertInstanceOf(InitialTeamSelectionRequired::class, $result);
        self::assertSame($email, $result->email);
        self::assertSame('Toto', $result->firstname);
        self::assertSame('Le Sysadmin', $result->lastname);
    }

    public function testAuthenticateRejectsNewUserWithoutTeam(): void
    {
        $email = 'ldap-user-without-team@example.com';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Could not find team ID to assign user!');
        $this->createLdap(
            array($this->getSearchResult($email, withTeam: false)),
            login: 'ldap-user-without-team',
            config: array(
                'ldap_search_attr' => 'uid',
                'saml_user_default' => '1',
            ),
        )->authenticate();
    }

    private function createLdap(
        array $searchResponses,
        ?string $userDn = self::DN,
        string $login = self::EMAIL,
        string $password = self::PASSWORD,
        array $config = array(),
        bool $credentialsValid = true,
    ): Ldap {
        $this->ldapFake = new LdapFake();
        $this->ldapFake->expect(
            LdapFake::operation('bind')->once()->with($this->ldapConfig['username'], $this->ldapConfig['password'])->andReturnResponse(),
        );

        foreach ($searchResponses as $response) {
            $this->ldapFake->expect(LdapFake::operation('search')->once()->andReturn($response));
        }

        if ($userDn !== null) {
            $bind = LdapFake::operation('bind')->once()->with($userDn, $password);
            $this->ldapFake->expect($credentialsValid ? $bind->andReturnResponse() : $bind->andReturnErrorResponse());
            $this->ldapFake->expect(
                LdapFake::operation('bind')->once()->with($this->ldapConfig['username'], $this->ldapConfig['password'])->andReturnResponse(),
            );
        }

        $connection = new ConnectionFake($this->ldapConfig, $this->ldapFake);
        return new Ldap($connection, new Entry(), array_replace($this->configArr, $config), $login, $password);
    }

    private function getSearchResult(string $email = self::EMAIL, bool $withTeam = true): array
    {
        $record = array(
            'dn' => array(self::DN),
            'mail' => array($email),
            'givenname' => array('Toto'),
            'sn' => array('Le Sysadmin'),
        );
        if ($withTeam) {
            $record['ou'] = array('Alpha');
        }

        return array($record);
    }
}
