<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use DateTimeImmutable;
use Elabftw\Elabftw\Authentication;
use Elabftw\Enums\AuthMethod;
use Elabftw\Enums\Messages;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Users\ExistingUser;
use Elabftw\Models\Users\Users;
use Elabftw\Models\Users\ValidatedUser;
use Elabftw\Services\UsersHelper;
use Lcobucci\JWT\Signer\Key\InMemory;
use OneLogin\Saml2\Auth as SamlAuthLib;
use PHPUnit\Framework\TestCase;

use function _;
use function array_column;
use function sort;
use function str_repeat;

final class SamlTest extends TestCase
{
    private const string REQUEST_ID = '_saml-test-request-id';

    private const string SESSION_INDEX = 'abcdef';

    private const string NAME_ID = 'saml-name-id';

    private const string NAME_ID_FORMAT = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';

    private array $configArr;

    private array $settings;

    private array $samlUserdata;

    protected function setUp(): void
    {
        $this->configArr = array(
            'saml_debug' => '0',
            'saml_sync_teams' => '0',
            'saml_team_default' => '2',
            'saml_user_default' => '0',
            'saml_fallback_orgid' => '0',
            'saml_sync_email_idp' => '0',
            'saml_team_create' => '1',
            'user_msg_need_local_account_created' => 'yep',
        );

        $this->settings = array(
            'idp' => array(
                'emailAttr' => 'User.email',
                'teamAttr' => 'User.team',
                'fnameAttr' => 'User.FirstName',
                'lnameAttr' => 'User.LastName',
                'orgidAttr' => 'internal_id',
                'orcidAttr' => 'orcid',
            ),
        );

        $this->samlUserdata = array(
            'User.email' => 'toto@yopmail.com',
            'User.FirstName' => 'Toto',
            'User.LastName' => 'Le Sysadmin',
            'User.team' => array('Alpha'),
        );
    }

    public function testExistingUserReturnsSamlAuthentication(): void
    {
        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse(),
        );

        self::assertSame(1, $authentication->userid);
        self::assertSame(AuthMethod::Saml, $authentication->method);
    }

    public function testExistingUserDoesNotNeedTeamAttributeWhenSyncIsDisabled(): void
    {
        $samlUserdata = $this->samlUserdata;
        unset($samlUserdata['User.team']);

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata),
        );

        self::assertSame(1, $authentication->userid);
    }

    public function testExistingUserDoesNotNeedDefaultTeam(): void
    {
        $samlUserdata = $this->samlUserdata;
        unset($samlUserdata['User.team']);

        $config = $this->configArr;
        $config['saml_team_default'] = '0';

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame(1, $authentication->userid);
    }

    public function testSessionIndexIsNullBeforeResponseIsProcessed(): void
    {
        self::assertNull($this->createSaml()->getSessionIndex());
    }

    public function testSessionIndexIsStoredAfterResponseIsProcessed(): void
    {
        $saml = $this->createSaml();
        $saml->assertIdpResponse(self::REQUEST_ID);

        self::assertSame(self::SESSION_INDEX, $saml->getSessionIndex());
    }

    public function testRequestIdIsPassedToSamlLibrary(): void
    {
        $SamlAuthLib = $this->createMock(
            SamlAuthLib::class,
        );

        $SamlAuthLib->expects(self::once())
            ->method('processResponse')
            ->with(self::REQUEST_ID);

        // Stop processing after processResponse().
        $SamlAuthLib->method('getErrors')
            ->willReturn(array('stop'));

        $saml = new Saml(
            $SamlAuthLib,
            $this->configArr,
            $this->settings,
        );

        $this->expectException(
            UnauthorizedException::class,
        );

        $saml->assertIdpResponse(
            self::REQUEST_ID,
        );
    }

    public function testFailedAuthenticationIsRejected(): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Authentication with IDP failed!');

        $this->createSaml(
            authenticated: false,
        )->assertIdpResponse(self::REQUEST_ID);
    }

    public function testSamlErrorsAreHiddenWhenDebugIsDisabled(): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage(Messages::GenericError->toHuman());

        $this->createSaml(
            errors: array('first error', 'second error'),
        )->assertIdpResponse(self::REQUEST_ID);
    }

    public function testSamlErrorsAreExposedWhenDebugIsEnabled(): void
    {
        $config = $this->configArr;
        $config['saml_debug'] = '1';

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('first error, second error');

        $this->createSaml(
            config: $config,
            errors: array('first error', 'second error'),
        )->assertIdpResponse(self::REQUEST_ID);
    }

    public function testMissingDebugSettingUsesGenericError(): void
    {
        $config = $this->configArr;
        unset($config['saml_debug']);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage(Messages::GenericError->toHuman());

        $this->createSaml(
            config: $config,
            errors: array('secret debug detail'),
        )->assertIdpResponse(self::REQUEST_ID);
    }

    public function testMissingEmailAttributeIsRejected(): void
    {
        $samlUserdata = $this->samlUserdata;
        unset($samlUserdata['User.email']);

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Could not find attribute "User.email" in response from IDP! Aborting.');

        $this->assertIdpResponse($samlUserdata);
    }

    public function testEmptyEmailAttributeIsRejected(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = '';

        $this->expectException(ImproperActionException::class);

        $this->assertIdpResponse($samlUserdata);
    }

    public function testEmptyEmailArrayIsRejected(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = array();

        $this->expectException(ImproperActionException::class);

        $this->assertIdpResponse($samlUserdata);
    }

    public function testNonStringEmailAttributeIsRejected(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 42;

        $this->expectException(ImproperActionException::class);

        $this->assertIdpResponse($samlUserdata);
    }

    public function testEmailArrayUsesFirstValue(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = array('toto@yopmail.com', 'ignored@example.com');

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata),
        );

        self::assertSame(1, $authentication->userid);
    }

    public function testUserCreationDisabledUsesConfiguredMessage(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-user-creation-disabled@example.com';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('yep');

        $this->assertIdpResponse($samlUserdata);
    }

    public function testUserCreationDisabledUsesDefaultMessage(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-user-default-message@example.com';

        $config = $this->configArr;
        unset($config['saml_user_default']);
        unset($config['user_msg_need_local_account_created']);

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage(
            _('Could not find an existing user. Ask a Sysadmin to create your account.'),
        );

        $this->assertIdpResponse($samlUserdata, $config);
    }

    public function testNewUserUsesConfiguredDefaultTeamWhenIdpProvidesNoTeam(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-default-team@example.com';
        unset($samlUserdata['User.team']);

        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        $config['saml_team_default'] = '2';

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame(array('Microbiology group - Dr. Monod'), $this->getTeamNames($authentication->userid));
    }

    public function testNewUserUsesConfiguredDefaultTeamWhenIdpTeamIsEmptyString(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-empty-team-default@example.com';
        $samlUserdata['User.team'] = '';

        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        $config['saml_team_default'] = '2';

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame(array('Microbiology group - Dr. Monod'), $this->getTeamNames($authentication->userid));
    }

    public function testNewUserWithoutTeamAndWithoutDefaultTeamIsRejected(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-no-team@example.com';
        unset($samlUserdata['User.team']);

        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        $config['saml_team_default'] = '0';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Could not find team ID to assign user!');

        $this->assertIdpResponse($samlUserdata, $config);
    }

    public function testNewUserWithoutTeamAndWithoutDefaultTeamSettingIsRejected(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-no-team-setting@example.com';
        unset($samlUserdata['User.team']);

        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        unset($config['saml_team_default']);

        $this->expectException(ImproperActionException::class);

        $this->assertIdpResponse($samlUserdata, $config);
    }

    public function testNewUserCanRequireInitialTeamSelection(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-select-team@example.com';
        $samlUserdata['User.FirstName'] = 'Select';
        $samlUserdata['User.LastName'] = 'Team';
        $samlUserdata['internal_id'] = 'saml-select-team-orgid';
        $samlUserdata['orcid'] = '0000-0001-2345-6789';
        unset($samlUserdata['User.team']);

        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        $config['saml_team_default'] = '-1';

        $result = $this->assertIdpResponse($samlUserdata, $config);

        self::assertInstanceOf(InitialTeamSelectionRequired::class, $result);
        self::assertSame('saml-select-team@example.com', $result->email);
        self::assertSame('Select', $result->firstname);
        self::assertSame('Team', $result->lastname);
        self::assertSame('saml-select-team-orgid', $result->orgid);
        self::assertSame('0000-0001-2345-6789', $result->orcid);
    }

    public function testInitialTeamSelectionExtractsFirstArrayValues(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = array('saml-array-values@example.com', 'ignored@example.com');
        $samlUserdata['User.FirstName'] = array('Array', 'Ignored');
        $samlUserdata['User.LastName'] = array('Values', 'Ignored');
        $samlUserdata['internal_id'] = array('array-orgid', 'ignored');
        $samlUserdata['orcid'] = array('0000-0002-0000-0001', 'ignored');
        unset($samlUserdata['User.team']);

        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        $config['saml_team_default'] = '-1';

        $result = $this->assertIdpResponse($samlUserdata, $config);

        self::assertInstanceOf(InitialTeamSelectionRequired::class, $result);
        self::assertSame('saml-array-values@example.com', $result->email);
        self::assertSame('Array', $result->firstname);
        self::assertSame('Values', $result->lastname);
        self::assertSame('array-orgid', $result->orgid);
        self::assertSame('0000-0002-0000-0001', $result->orcid);
    }

    public function testInitialTeamSelectionNormalizesInvalidOptionalAttributes(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-invalid-optional-attributes@example.com';
        $samlUserdata['User.FirstName'] = 42;
        $samlUserdata['User.LastName'] = array();
        $samlUserdata['internal_id'] = 42;
        $samlUserdata['orcid'] = array();
        unset($samlUserdata['User.team']);

        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        $config['saml_team_default'] = '-1';

        $result = $this->assertIdpResponse($samlUserdata, $config);

        self::assertInstanceOf(InitialTeamSelectionRequired::class, $result);
        self::assertSame('Unknown', $result->firstname);
        self::assertSame('Unknown', $result->lastname);
        self::assertNull($result->orgid);
        self::assertNull($result->orcid);
    }

    public function testInitialTeamSelectionHandlesMissingOptionalAttributeSettings(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-missing-optional-settings@example.com';
        unset($samlUserdata['User.team']);

        $settings = $this->settings;
        unset($settings['idp']['fnameAttr']);
        unset($settings['idp']['lnameAttr']);
        unset($settings['idp']['orgidAttr']);
        unset($settings['idp']['orcidAttr']);

        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        $config['saml_team_default'] = '-1';

        $result = $this->assertIdpResponse($samlUserdata, $config, $settings);

        self::assertInstanceOf(InitialTeamSelectionRequired::class, $result);
        self::assertSame('Unknown', $result->firstname);
        self::assertSame('Unknown', $result->lastname);
        self::assertNull($result->orgid);
        self::assertNull($result->orcid);
    }

    public function testNewUserCanUseStringTeamFromIdp(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-string-team@example.com';
        $samlUserdata['User.team'] = 'Alpha';

        $config = $this->configArr;
        $config['saml_user_default'] = '1';

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame(array('Alpha'), $this->getTeamNames($authentication->userid));
    }

    public function testNewUserCanUseArrayOfTeamsFromIdp(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-array-teams@example.com';
        $samlUserdata['User.team'] = array('Bravo', 'Alpha');

        $config = $this->configArr;
        $config['saml_user_default'] = '1';

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame(array('Alpha', 'Bravo'), $this->getTeamNames($authentication->userid));
    }

    public function testInvalidTeamAttributeForNewUserIsRejected(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-invalid-team-type@example.com';
        $samlUserdata['User.team'] = 42;

        $config = $this->configArr;
        $config['saml_user_default'] = '1';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Invalid team attribute returned by IDP.');

        $this->assertIdpResponse($samlUserdata, $config);
    }

    public function testTeamCreationCanBeDisabledForNewUser(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-team-create-disabled@example.com';
        $samlUserdata['User.team'] = array('Alpha', 'SAML team that must not be created');

        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        $config['saml_team_create'] = '0';

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame(array('Alpha'), $this->getTeamNames($authentication->userid));
    }

    public function testNewUserIsRejectedWhenNoProvidedTeamExistsAndCreationIsDisabled(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-no-existing-team@example.com';
        $samlUserdata['User.team'] = array('SAML nonexistent team only');

        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        $config['saml_team_create'] = '0';

        $this->expectException(ImproperActionException::class);

        $this->assertIdpResponse($samlUserdata, $config);
    }

    public function testTeamCreationDefaultsToEnabled(): void
    {
        $teamName = 'SAML automatically created team';
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-team-create-default@example.com';
        $samlUserdata['User.team'] = array($teamName);

        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        unset($config['saml_team_create']);

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame(array($teamName), $this->getTeamNames($authentication->userid));
    }

    public function testTeamSynchronizationIsSkippedByDefault(): void
    {
        $user = $this->createLocalUser(
            'saml-sync-default-off@example.com',
            array('Alpha', 'Bravo'),
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $user->userData['email'];
        unset($samlUserdata['User.team']);

        $config = $this->configArr;
        unset($config['saml_sync_teams']);

        $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame(
            array('Alpha', 'Bravo'),
            $this->getTeamNames($user->getUserid()),
        );
    }

    public function testTeamSynchronizationAcceptsStringTeam(): void
    {
        $user = $this->createLocalUser(
            'saml-sync-string-team@example.com',
            array('Alpha', 'Bravo'),
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $user->userData['email'];
        $samlUserdata['User.team'] = 'Alpha';

        $config = $this->configArr;
        $config['saml_sync_teams'] = '1';

        $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame(
            array('Alpha'),
            $this->getTeamNames($user->getUserid()),
        );
    }

    public function testTeamSynchronizationAcceptsArrayOfTeams(): void
    {
        $user = $this->createLocalUser(
            'saml-sync-array-teams@example.com',
            array('Alpha'),
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $user->userData['email'];
        $samlUserdata['User.team'] = array('Bravo', 'Alpha');

        $config = $this->configArr;
        $config['saml_sync_teams'] = '1';

        $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame(
            array('Alpha', 'Bravo'),
            $this->getTeamNames($user->getUserid()),
        );
    }

    public function testTeamSynchronizationDoesNotCreateUnknownTeamWhenDisabled(): void
    {
        $user = $this->createLocalUser(
            'saml-sync-team-create-disabled@example.com',
            array('Alpha', 'Bravo'),
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $user->userData['email'];
        $samlUserdata['User.team'] = array('Alpha', 'SAML sync unknown team');

        $config = $this->configArr;
        $config['saml_sync_teams'] = '1';
        $config['saml_team_create'] = '0';

        $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame(
            array('Alpha'),
            $this->getTeamNames($user->getUserid()),
        );
    }

    public function testTeamSynchronizationCanCreateUnknownTeam(): void
    {
        $teamName = 'SAML sync newly created team';
        $user = $this->createLocalUser(
            'saml-sync-team-create-enabled@example.com',
            array('Alpha'),
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $user->userData['email'];
        $samlUserdata['User.team'] = array('Alpha', $teamName);

        $config = $this->configArr;
        $config['saml_sync_teams'] = '1';
        $config['saml_team_create'] = '1';

        $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame(
            array('Alpha', $teamName),
            $this->getTeamNames($user->getUserid()),
        );
    }

    public function testTeamSynchronizationRequiresConfiguredTeamAttribute(): void
    {
        $user = $this->createLocalUser(
            'saml-sync-no-team-config@example.com',
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $user->userData['email'];

        $settings = $this->settings;
        unset($settings['idp']['teamAttr']);

        $config = $this->configArr;
        $config['saml_sync_teams'] = '1';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage(
            'Cannot synchronize team(s) from IDP if no value is set for looking up team(s) in IDP response!',
        );

        $this->assertIdpResponse($samlUserdata, $config, $settings);
    }

    public function testTeamSynchronizationRejectsEmptyConfiguredTeamAttribute(): void
    {
        $user = $this->createLocalUser(
            'saml-sync-empty-team-config@example.com',
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $user->userData['email'];

        $settings = $this->settings;
        $settings['idp']['teamAttr'] = '';

        $config = $this->configArr;
        $config['saml_sync_teams'] = '1';

        $this->expectException(ImproperActionException::class);

        $this->assertIdpResponse($samlUserdata, $config, $settings);
    }

    public function testTeamSynchronizationRejectsMissingTeamValue(): void
    {
        $user = $this->createLocalUser(
            'saml-sync-missing-team-value@example.com',
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $user->userData['email'];
        unset($samlUserdata['User.team']);

        $config = $this->configArr;
        $config['saml_sync_teams'] = '1';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Could not find team(s) in IDP response!');

        $this->assertIdpResponse($samlUserdata, $config);
    }

    public function testTeamSynchronizationRejectsEmptyTeamValue(): void
    {
        $user = $this->createLocalUser(
            'saml-sync-empty-team-value@example.com',
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $user->userData['email'];
        $samlUserdata['User.team'] = '';

        $config = $this->configArr;
        $config['saml_sync_teams'] = '1';

        $this->expectException(ImproperActionException::class);

        $this->assertIdpResponse($samlUserdata, $config);
    }

    public function testTeamSynchronizationRejectsInvalidTeamType(): void
    {
        $user = $this->createLocalUser(
            'saml-sync-invalid-team-value@example.com',
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $user->userData['email'];
        $samlUserdata['User.team'] = 42;

        $config = $this->configArr;
        $config['saml_sync_teams'] = '1';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Invalid team attribute returned by IDP.');

        $this->assertIdpResponse($samlUserdata, $config);
    }

    public function testOrgidFallbackFindsExistingUser(): void
    {
        $user = $this->createLocalUser(
            'saml-orgid-original@example.com',
            orgid: 'saml-orgid-match',
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-orgid-new-email@example.com';
        $samlUserdata['internal_id'] = 'saml-orgid-match';

        $config = $this->configArr;
        $config['saml_fallback_orgid'] = '1';

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame($user->getUserid(), $authentication->userid);
    }

    public function testOrgidArrayUsesFirstValueForFallback(): void
    {
        $user = $this->createLocalUser(
            'saml-orgid-array-original@example.com',
            orgid: 'saml-orgid-array-match',
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-orgid-array-new@example.com';
        $samlUserdata['internal_id'] = array('saml-orgid-array-match', 'ignored');

        $config = $this->configArr;
        $config['saml_fallback_orgid'] = '1';

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame($user->getUserid(), $authentication->userid);
    }

    public function testOrgidFallbackIsDisabledByDefault(): void
    {
        $this->createLocalUser(
            'saml-fallback-disabled-original@example.com',
            orgid: 'saml-fallback-disabled-orgid',
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-fallback-disabled-new@example.com';
        $samlUserdata['internal_id'] = 'saml-fallback-disabled-orgid';

        $config = $this->configArr;
        unset($config['saml_fallback_orgid']);

        $this->expectException(ImproperActionException::class);

        $this->assertIdpResponse($samlUserdata, $config);
    }

    public function testOrgidFallbackWithoutOrgidCannotMatch(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-fallback-no-orgid@example.com';
        unset($samlUserdata['internal_id']);

        $config = $this->configArr;
        $config['saml_fallback_orgid'] = '1';

        $this->expectException(ImproperActionException::class);

        $this->assertIdpResponse($samlUserdata, $config);
    }

    public function testMissingOrgidFallbackCanCreateUserWhenAllowed(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-fallback-miss-create@example.com';
        $samlUserdata['internal_id'] = 'saml-orgid-that-does-not-exist';
        $samlUserdata['User.team'] = 'Alpha';

        $config = $this->configArr;
        $config['saml_fallback_orgid'] = '1';
        $config['saml_user_default'] = '1';

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame(
            $authentication->userid,
            ExistingUser::fromEmail('saml-fallback-miss-create@example.com')->getUserid(),
        );
    }

    public function testOrgidFallbackCanSynchronizeEmail(): void
    {
        $user = $this->createLocalUser(
            'saml-sync-email-old@example.com',
            orgid: 'saml-sync-email-orgid',
        );

        $newEmail = 'saml-sync-email-new@example.com';
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $newEmail;
        $samlUserdata['internal_id'] = 'saml-sync-email-orgid';

        $config = $this->configArr;
        $config['saml_fallback_orgid'] = '1';
        $config['saml_sync_email_idp'] = '1';

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame($user->getUserid(), $authentication->userid);
        self::assertSame(
            $user->getUserid(),
            ExistingUser::fromEmail($newEmail)->getUserid(),
        );
    }

    public function testOrgidFallbackDoesNotSynchronizeEmailByDefault(): void
    {
        $oldEmail = 'saml-no-sync-email-old@example.com';
        $user = $this->createLocalUser(
            $oldEmail,
            orgid: 'saml-no-sync-email-orgid',
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'saml-no-sync-email-new@example.com';
        $samlUserdata['internal_id'] = 'saml-no-sync-email-orgid';

        $config = $this->configArr;
        $config['saml_fallback_orgid'] = '1';
        unset($config['saml_sync_email_idp']);

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata, $config),
        );

        self::assertSame($user->getUserid(), $authentication->userid);
        self::assertSame(
            $user->getUserid(),
            ExistingUser::fromEmail($oldEmail)->getUserid(),
        );
    }

    public function testUserAttributesAreSynchronized(): void
    {
        $email = 'saml-profile-sync@example.com';
        $user = $this->createLocalUser(
            $email,
            firstname: 'Before',
            lastname: 'User',
            orgid: 'saml-old-orgid',
            orcid: '0000-0002-1825-0097',
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $email;
        $samlUserdata['User.FirstName'] = 'After';
        $samlUserdata['User.LastName'] = 'Login';
        $samlUserdata['internal_id'] = 'saml-new-orgid';
        $samlUserdata['orcid'] = '0000-0001-5109-3700';

        $authentication = $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata),
        );

        self::assertSame($user->getUserid(), $authentication->userid);

        $fresh = new ExistingUser($user->getUserid());
        self::assertSame('After', $fresh->userData['firstname']);
        self::assertSame('Login', $fresh->userData['lastname']);
        self::assertSame('saml-new-orgid', $fresh->userData['orgid']);
        self::assertSame('0000-0001-5109-3700', $fresh->userData['orcid']);
    }

    public function testNamesAreNotSynchronizedUnlessBothArePresent(): void
    {
        $email = 'saml-partial-name-sync@example.com';
        $user = $this->createLocalUser(
            $email,
            firstname: 'Original',
            lastname: 'Name',
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $email;
        $samlUserdata['User.FirstName'] = 'Changed';
        unset($samlUserdata['User.LastName']);

        $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata),
        );

        $fresh = new ExistingUser($user->getUserid());
        self::assertSame('Original', $fresh->userData['firstname']);
        self::assertSame('Name', $fresh->userData['lastname']);
    }

    public function testMissingOptionalAttributesDoNotOverwriteExistingValues(): void
    {
        $email = 'saml-missing-optional-sync@example.com';
        $user = $this->createLocalUser(
            $email,
            orgid: 'saml-preserved-orgid',
            orcid: '0000-0002-1825-0097',
        );

        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = $email;
        unset($samlUserdata['internal_id']);
        unset($samlUserdata['orcid']);

        $this->assertAuthentication(
            $this->assertIdpResponse($samlUserdata),
        );

        $fresh = new ExistingUser($user->getUserid());
        self::assertSame('saml-preserved-orgid', $fresh->userData['orgid']);
        self::assertSame('0000-0002-1825-0097', $fresh->userData['orcid']);
    }

    public function testEncodeDecodeTokenPreservesSamlLogoutClaims(): void
    {
        $saml = $this->createSaml();
        $saml->assertIdpResponse(self::REQUEST_ID);

        $token = $saml->encodeToken(42);
        [$sid, $idpId, $nameId, $nameIdFormat] = Saml::decodeToken($token);

        self::assertSame(self::SESSION_INDEX, $sid);
        self::assertSame(42, $idpId);
        self::assertSame(self::NAME_ID, $nameId);
        self::assertSame(self::NAME_ID_FORMAT, $nameIdFormat);
    }

    public function testEncodeDecodeTokenSupportsNullSessionIndex(): void
    {
        $saml = $this->createSaml(sessionIndex: null);
        $saml->assertIdpResponse(self::REQUEST_ID);

        [$sid] = Saml::decodeToken($saml->encodeToken(42));

        self::assertNull($sid);
    }

    public function testDecodeTokenRejectsEmptyToken(): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Decoding JWT Token failed');

        Saml::decodeToken('');
    }

    public function testDecodeTokenRejectsUndecodableToken(): void
    {
        $this->expectException(UnauthorizedException::class);

        Saml::decodeToken('..');
    }

    public function testDecodeTokenRejectsInvalidTokenStructure(): void
    {
        $this->expectException(UnauthorizedException::class);

        Saml::decodeToken('this can not be parsed');
    }

    public function testDecodeTokenRejectsWrongAudience(): void
    {
        $config = Saml::getJWTConfig();
        $now = new DateTimeImmutable();

        $token = $config->builder()
            ->permittedFor('wrong-audience')
            ->issuedAt($now)
            ->withClaim('sid', self::SESSION_INDEX)
            ->withClaim('idp_id', 1)
            ->withClaim('nameid', self::NAME_ID)
            ->withClaim('nameid_format', self::NAME_ID_FORMAT)
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $this->expectException(UnauthorizedException::class);

        Saml::decodeToken($token);
    }

    public function testDecodeTokenRejectsInvalidSignature(): void
    {
        $config = Saml::getJWTConfig();
        $now = new DateTimeImmutable();

        $token = $config->builder()
            ->permittedFor('saml-session')
            ->issuedAt($now)
            ->withClaim('sid', self::SESSION_INDEX)
            ->withClaim('idp_id', 1)
            ->withClaim('nameid', self::NAME_ID)
            ->withClaim('nameid_format', self::NAME_ID_FORMAT)
            ->getToken(
                $config->signer(),
                InMemory::plainText(str_repeat('x', 64)),
            )
            ->toString();

        $this->expectException(UnauthorizedException::class);

        Saml::decodeToken($token);
    }

    private function assertIdpResponse(
        ?array $samlUserdata = null,
        ?array $config = null,
        ?array $settings = null,
    ): Authentication|InitialTeamSelectionRequired {
        return $this->createSaml(
            $samlUserdata,
            $config,
            $settings,
        )->assertIdpResponse(self::REQUEST_ID);
    }

    private function createSaml(
        ?array $samlUserdata = null,
        ?array $config = null,
        ?array $settings = null,
        array $errors = array(),
        bool $authenticated = true,
        ?string $sessionIndex = self::SESSION_INDEX,
        ?string $nameId = self::NAME_ID,
        ?string $nameIdFormat = self::NAME_ID_FORMAT,
    ): Saml {
        $samlUserdata ??= $this->samlUserdata;
        $config ??= $this->configArr;
        $settings ??= $this->settings;

        $SamlAuthLib = $this->createMock(SamlAuthLib::class);
        $SamlAuthLib->method('getErrors')->willReturn($errors);
        $SamlAuthLib->method('isAuthenticated')->willReturn($authenticated);
        $SamlAuthLib->method('getAttributes')->willReturn($samlUserdata);
        $SamlAuthLib->method('getSessionIndex')->willReturn($sessionIndex);
        $SamlAuthLib->method('getNameId')->willReturn($nameId);
        $SamlAuthLib->method('getNameIdFormat')->willReturn($nameIdFormat);

        return new Saml(
            $SamlAuthLib,
            $config,
            $settings,
        );
    }

    private function assertAuthentication(
        Authentication|InitialTeamSelectionRequired $result,
    ): Authentication {
        if (!$result instanceof Authentication) {
            self::fail('Expected SAML response to resolve to Authentication.');
        }

        self::assertSame(AuthMethod::Saml, $result->method);

        return $result;
    }

    /**
     * @param list<string|int> $teams
     */
    private function createLocalUser(
        string $email,
        array $teams = array('Alpha'),
        string $firstname = 'Local',
        string $lastname = 'User',
        ?string $orgid = null,
        ?string $orcid = null,
    ): Users {
        return ValidatedUser::fromExternal(
            $email,
            $teams,
            $firstname,
            $lastname,
            orgid: $orgid,
            orcid: $orcid,
            allowTeamCreation: false,
        );
    }

    /**
     * @return list<string>
     */
    private function getTeamNames(int $userid): array
    {
        $names = array_column(
            new UsersHelper($userid)->getTeamsFromUserid(),
            'name',
        );
        sort($names);

        return $names;
    }
}
