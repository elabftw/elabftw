<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use Elabftw\Auth\Saml as SamlAuth;
use Elabftw\Elabftw\AuthResponse;
use Elabftw\Elabftw\IdpsHelper;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Config;
use Elabftw\Models\Idps;
use OneLogin\Saml2\Auth as SamlAuthLib;

class SamlTest extends \PHPUnit\Framework\TestCase
{
    private array $configArr;

    private SamlAuthLib $SamlAuthLib;

    private array $samlUserdata;

    private array $settings;

    private int $idpId;

    private IdpsHelper $IdpsHelper;

    protected function setUp(): void
    {
        $cert = '-----BEGIN CERTIFICATE-----MIIELDCCAxggAwIBAgIUaFt6ppX/TrAJo207cGFEJEdGaLgwDQYJKoZIhvcNAQEFBQAwXaELMAkGA1UEBhMCVVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAyOTU4MB4XDTE3MDMxOTExMzExNloXDTIyMDMyMDExMzExNlowXzELMAkGA1UEBhMCVVMxFzAVBgNVBAoMDkluc3RpdHV0IEN1cmllMRUwEwYDVQQLDAxPbmVMb2dpbiBJZFAxIDAeBgNVBAMMF09uZUxvZ2luIEFjY291bnQgMTAyOTU4MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzNKk3lhtLUJKvyl+0HZF3xpsjYRFT0HR30xADhRUGT/7lwVl3SnkgN6Us6NtOdKRFqFntz37s4qkmbzD0tGG6GirIIvgFx8HKhTwYgjsMsC/+NcS854zB/9pDlwNpZwhjGXZgE9YQUXuiZp1W/1kE+KZANr1KJKjtlsiWjNWah9VXLKCjQfKHdgYxSiSW9mv/Phz6ZjW0M3wdnJQRGg0iUzDxWhYp7sGUvjIhPtdb+VCYVm2MymYESXbkXH60kG26TPvvJrELPkAJ54RWsuPkWADBZxIozeS/1Hehjg2vIcH7T/x41+qSN9IzlhWQTYtVCkpR2ShNbXL7AUXMM5bsQIDAQABo4HfMIHcMAwGA1UdEwEB/wQCMAAwHQYDVR0OBBYEFPERoVBCoadgrSI2Wdy7zPWIUuWyMIGcBgNVHSMEgZQwgZGAFPERoVBCoadgrSI2Wdy7zPWIUuWyoWOkYTBfMQswCQYDVQQGEwJVUzEXMBUGA1UECgwOSW5zdGl0dXQgQ3VyaWUxFTATBgNVBAsMDE9uZUxvZ2luIElkUDEgMB4GA1UEAwwXT25lTG9naW4gQWNjb3VudCAxMDI5NTiCFGhbeqRV/06wCaNtO3BhRCRHRmi4MA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEAZ7CjWWuRdwJFBsUyEewobXi/yYr/AnlmkjNDOJyDGs2DHNHVEmrm7z4LWmzLHWPfzAu4w55wovJg8jrjhTaFiBO5zcAa/3XQyI4atKKu4KDlZ6cM/2a14mURBhPT6I+ZZUVeX6411AgWQmohsESXmamEZtd89aOWfwlTFfAw8lbe3tHRkZvD5Y8N5oawvdHSurapSo8fde/oWUkO8I3JyyTUzlFOA6ri8bbnWz3YnofB5TXoOtdXui1SLuVJu8ABBEbhgv/m1o36VdOoikJjlZOUjfX5xjEupRkX/YTp0yfNmxt71kjgVLs66b1+dRG1c2Zk0y2rp0x3y3KG6K61Ug==-----END CERTIFICATE-----';

        // Insert an IDP
        $Idps = new Idps();
        $Idps->postAction(Action::Create, array(
            'name' => 'testidp',
            'entityid' => 'https://app.onelogin.com/',
            'sso_url' => 'https://onelogin.com/',
            'sso_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'slo_url' => 'https://onelogin.com/',
            'slo_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'x509' => $cert,
            'x509_new' => $cert,
            'email_attr' => 'User.email',
            'team_attr' => 'User.team',
            'fname_attr' => 'User.FirstName',
            'lname_attr' => 'User.LastName',
            'orgid_attr' => 'internal_id',
        ));

        $this->configArr = array(
            'debug' => '0',
            'saml_sync_teams' => '0',
            'saml_team_default' => '2',
            'saml_user_default' => '0',
            'saml_fallback_orgid' => '0',
            'saml_sync_email_idp' => '0',
            'user_msg_need_local_account_created' => 'yep',
        );

        // don't use the real saml lib but create a mock
        $this->SamlAuthLib = $this->createMock(SamlAuthLib::class);
        $this->SamlAuthLib->method('login')->willReturn(null);
        $this->SamlAuthLib->method('processResponse')->willReturn(null);
        $this->SamlAuthLib->method('getErrors')->willReturn(null);
        $this->SamlAuthLib->method('getSessionIndex')->willReturn('abcdef');
        $this->SamlAuthLib->method('isAuthenticated')->willReturn(true);

        // create fake saml idp response
        $this->samlUserdata = array();
        $this->samlUserdata['User.email'] = 'toto@yopmail.com';
        $this->samlUserdata['User.firstname'] = 'Toto';
        $this->samlUserdata['User.lastname'] = 'FTW';
        $this->samlUserdata['User.team'] = 'Alpha';
        $this->SamlAuthLib->method('getAttributes')->willReturn($this->samlUserdata);

        $this->IdpsHelper = new IdpsHelper(Config::getConfig(), $Idps);
        $this->idpId = $Idps->readAll()[0]['id'];
        $this->settings = $this->IdpsHelper->getSettings($this->idpId);
    }

    public function testTryAuth(): void
    {
        $AuthService = new SamlAuth($this->SamlAuthLib, $this->configArr, $this->settings);
        $authResponse = $AuthService->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $authResponse);
    }

    public function testAssertIdpResponse(): void
    {
        // happy path
        $AuthService = new SamlAuth($this->SamlAuthLib, $this->configArr, $this->settings);
        $authResponse = $AuthService->assertIdpResponse();
        $this->assertInstanceOf(AuthResponse::class, $authResponse);
        $this->assertEquals(1, $authResponse->userid);
        $this->assertFalse($authResponse->isAnonymous);
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    public function testgetSettings(): void
    {
        $this->assertIsArray($this->IdpsHelper->getSettings($this->idpId));
    }

    public function testgetSettingsByEntityId(): void
    {
        $this->assertIsArray($this->IdpsHelper->getSettingsByEntityId('https://app.onelogin.com/'));
    }

    public function testAssertIdpResponseSyncTeams(): void
    {
        $configArr = $this->configArr;
        $configArr['saml_sync_teams'] = '1';
        $AuthService = new SamlAuth($this->SamlAuthLib, $configArr, $this->settings);
        $authResponse = $AuthService->assertIdpResponse();
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    public function testAssertIdpResponseFailedAuth(): void
    {
        // now try with a failed auth
        // don't use the real saml lib but create a mock
        $this->SamlAuthLib = $this->createMock(SamlAuthLib::class);
        $this->SamlAuthLib->method('login')->willReturn(null);
        $this->SamlAuthLib->method('processResponse')->willReturn(null);
        $this->SamlAuthLib->method('getErrors')->willReturn(null);
        $this->SamlAuthLib->method('isAuthenticated')->willReturn(false);
        $AuthService = new SamlAuth($this->SamlAuthLib, $this->configArr, $this->settings);
        $this->expectException(UnauthorizedException::class);
        $AuthService->assertIdpResponse();
    }

    /**
     * Idp doesn't send back a team
     */
    public function testAssertIdpResponseNoTeamResponse(): void
    {
        $samlUserdata = $this->samlUserdata;
        unset($samlUserdata['User.team']);

        $authResponse = $this->getAuthResponse($samlUserdata);
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    /**
     * Idp doesn't send back a team and there are no default team
     */
    public function testAssertIdpResponseNoTeamResponseNoDefaultTeam(): void
    {
        $samlUserdata = $this->samlUserdata;
        unset($samlUserdata['User.team']);
        // same but with no configured default team
        $config = $this->configArr;
        $config['saml_team_default'] = '0';
        $authResponse = $this->getAuthResponse($samlUserdata, $config);
        // as user exists already, they'll be in team 1
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    /**
     * Idp sends an array of teams
     */
    public function testAssertIdpResponseTeamsArrayResponse(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.team'] = array('Alpha');

        $authResponse = $this->getAuthResponse($samlUserdata);
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    /**
     * Idp sends an array of email
     */
    public function testAssertIdpResponseEmailArrayResponse(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = array('toto@yopmail.com');

        $authResponse = $this->getAuthResponse($samlUserdata);
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    /**
     * Idp doesn't send back an email
     */
    public function testAssertIdpResponseNoEmail(): void
    {
        $samlUserdata = $this->samlUserdata;
        unset($samlUserdata['User.email']);

        $this->expectException(ImproperActionException::class);
        $this->getAuthResponse($samlUserdata);
    }

    /**
     * User is not found with email but with orgid
     */
    public function testMatchUserWithOrgid(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['internal_id'] = 'internal_id_1';
        $samlUserdata['User.email'] = 'userchangedemail@example.com';
        $this->settings['idp']['orgidAttr'] = 'internal_id';
        $config = $this->configArr;
        $config['saml_fallback_orgid'] = '1';

        $authResponse = $this->getAuthResponse($samlUserdata, $config);
        $this->assertEquals(1, $authResponse->userid);
    }

    /**
     * User is not found with email but with orgid and we update the email
     */
    public function testMatchUserWithOrgidAndChangeEmail(): void
    {
        $samlUserdata = $this->samlUserdata;
        // this will match the user with original email "somesamluser@example.com"
        $samlUserdata['internal_id'] = 'internal_id_42';
        // we assign a new email to that user from the idp response
        $samlUserdata['User.email'] = 'somesamluser42@example.com';
        // make sure the orgid is picked up
        $this->settings['idp']['orgidAttr'] = 'internal_id';
        $config = $this->configArr;
        $config['saml_fallback_orgid'] = '1';
        // the email will be modified here, and updated with the value coming from idp
        $config['saml_sync_email_idp'] = '1';

        $authResponse = $this->getAuthResponse($samlUserdata, $config);
        $this->assertEquals(7, $authResponse->userid);
    }

    /**
     * User is not found with email nor with orgid and we can't create new user
     */
    public function testMatchUserWithOrgidFail(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['internal_id'] = 'internal_id_23';
        $samlUserdata['User.email'] = 'userchangedemailagain@example.com';

        // exception will be thrown because we have saml_user_default to 0
        $this->expectException(ImproperActionException::class);
        $this->getAuthResponse($samlUserdata);
    }

    /**
     * User is not found with email nor with orgid so the user is created
     */
    public function testMatchUserWithOrgidAndCreateUser(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'a_new_never_seen_before_user@example.com';
        $settings = $this->settings;
        unset($settings['idp']['teamAttr']);

        // create the user on the fly
        $config = $this->configArr;
        $config['saml_user_default'] = '1';

        $authResponse = $this->getAuthResponse($samlUserdata, $config, $settings);
        $this->assertIsInt($authResponse->userid);
    }

    /**
     * User is not found with email nor with orgid so the user is created but we cannot find a team!
     */
    public function testCreateUserButTeamCannotBeFound(): void
    {
        // copy so we don't pollute global
        $samlUserdata = $this->samlUserdata;
        $settings = $this->settings;
        $config = $this->configArr;

        // use a fresh email address
        $samlUserdata['User.email'] = 'a_new_never_seen_before_user_for_real@example.com';

        // remove the team attribute setting
        unset($settings['idp']['teamAttr']);

        // create the user on the fly
        $config['saml_user_default'] = '1';
        // throw error if team cannot be found
        $config['saml_team_default'] = '0';

        $this->expectException(ImproperActionException::class);
        $this->getAuthResponse($samlUserdata, $config, $settings);
    }

    /**
     * User is not found with email nor with orgid so the user is created and we let them select a team
     */
    public function testCreateUserButTeamMustBeSelected(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'a_new_never_seen_before_user_for_real@example.com';
        $samlUserdata['internal_id'] = 'something else';
        // remove the team attribute setting
        $settings = $this->settings;
        unset($settings['idp']['teamAttr']);

        // create the user on the fly
        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        // let user select a team
        $config['saml_team_default'] = '-1';
        // we try to match with orgid too but it won't work
        $config['saml_fallback_orgid'] = '1';

        $authResponse = $this->getAuthResponse($samlUserdata, $config, $settings);
        $this->assertEmpty($authResponse->selectableTeams);
    }

    /**
     * User is not found with email nor with orgid so the user is created
     */
    public function testCreateUserWithTeamsFromIdp(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'a_new_never_seen_before_user_for_real@example.com';
        $samlUserdata['User.team'] = 'Bravo';

        // create the user on the fly
        $config = $this->configArr;
        $config['saml_user_default'] = '1';

        $authResponse = $this->getAuthResponse($samlUserdata, $config);
        $this->assertEquals(2, $authResponse->selectedTeam);
    }

    public function testCreateUserWithTeamsFromIdpButConfigIsEmpty(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'a_new_never_seen_before_user_for_real_yes@example.com';
        $samlUserdata['User.team'] = 'Bravo';
        $settings = $this->settings;
        // set an empty idp team attribute
        $settings['idp']['teamAttr'] = '';

        // create the user on the fly
        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        // try to synchronize the teams from idp but the team attribute is empty
        $config['saml_sync_teams'] = '1';

        $this->expectException(ImproperActionException::class);
        $this->getAuthResponse($samlUserdata, $config, $settings);
    }

    public function testCreateUserWithTeamsFromIdpAndTeamsIsArray(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'a_new_never_seen_before_user_for_real_yes@example.com';
        $samlUserdata['User.team'] = array('Bravo', 'Alpha');
        $settings = $this->settings;
        // set an empty idp team attribute
        $settings['idp']['teamAttr'] = 'User.team';

        // create the user on the fly
        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        // try to synchronize the teams from idp but the team attribute is empty
        $config['saml_sync_teams'] = '1';

        $response = $this->getAuthResponse($samlUserdata, $config, $settings);
        $this->assertEquals(2, count($response->selectableTeams));
    }

    public function testCreateUserWithTeamsFromIdpButIdpValueIsEmpty(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'a_new_never_seen_before_user_for_real_yes@example.com';
        $samlUserdata['User.team'] = '';
        $settings = $this->settings;

        // create the user on the fly
        $config = $this->configArr;
        $config['saml_user_default'] = '1';
        // try to synchronize the teams from idp but the team attribute is empty
        $config['saml_sync_teams'] = '1';

        $this->expectException(ImproperActionException::class);
        $this->getAuthResponse($samlUserdata, $config, $settings);
    }

    /**
     * User is not found with email nor with orgid so the user is created in several teams
     */
    public function testCreateUserWithTeamsFromIdpInSeveralTeams(): void
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = 'a_new_never_seen_before_user_for_real_again@example.com';
        $samlUserdata['User.team'] = array('Bravo', 'Fresh new team');

        // create the user on the fly
        $config = $this->configArr;
        $config['saml_user_default'] = '1';

        $authResponse = $this->getAuthResponse($samlUserdata, $config);
        $this->assertEquals(2, count($authResponse->selectableTeams));
    }

    /**
     * Try with errors in the response
     */
    public function testAssertIdpResponseError(): void
    {
        $this->SamlAuthLib = $this->createMock(SamlAuthLib::class);
        $this->SamlAuthLib->method('getErrors')->willReturn(array('Error' => 'Something went wrong!'));
        $AuthService = new SamlAuth($this->SamlAuthLib, $this->configArr, $this->settings);
        $this->expectException(UnauthorizedException::class);
        $AuthService->assertIdpResponse();
    }

    /**
     * With debug mode on and errors
     */
    public function testAssertIdpResponseErrorDebug(): void
    {
        $this->SamlAuthLib = $this->createMock(SamlAuthLib::class);
        $this->SamlAuthLib->method('getErrors')->willReturn(array('Error' => 'Something went wrong!'));
        $configArr = $this->configArr;
        $configArr['debug'] = '1';
        $AuthService = new SamlAuth($this->SamlAuthLib, $configArr, $this->settings);
        $this->expectException(UnauthorizedException::class);
        $AuthService->assertIdpResponse();
    }

    public function testGetSessionIndex(): void
    {
        $AuthService = new SamlAuth($this->SamlAuthLib, $this->configArr, $this->settings);
        $AuthService->assertIdpResponse();
        $this->assertEquals('abcdef', $AuthService->getSessionIndex());
    }

    public function testEncodeDecodeToken(): void
    {
        $AuthService = new SamlAuth($this->SamlAuthLib, $this->configArr, $this->settings);
        $AuthService->assertIdpResponse();

        $token = $AuthService->encodeToken(1);
        $this->assertIsString($token);

        [$sid, $idpId] = SamlAuth::decodeToken($token);
        $this->assertEquals('abcdef', $sid);
        $this->assertEquals(1, $idpId);
    }

    public function testEmptyToken(): void
    {
        $this->expectException(UnauthorizedException::class);
        SamlAuth::decodeToken('');
    }

    public function testUndecodableToken(): void
    {
        $this->expectException(UnauthorizedException::class);
        SamlAuth::decodeToken('..');
    }

    public function testNotParsableToken(): void
    {
        $this->expectException(UnauthorizedException::class);
        SamlAuth::decodeToken('this can not be parsed');
    }

    /**
     * Helper function to avoid code repetition
     */
    private function getAuthResponse(?array $samlUserdata = null, ?array $config = null, ?array $settings = null): AuthResponse
    {
        $samlUserdata ??= $this->samlUserdata;
        $config ??= $this->configArr;
        $settings ??= $this->settings;

        $SamlAuthLib = $this->createMock(SamlAuthLib::class);
        $SamlAuthLib->method('login')->willReturn(null);
        $SamlAuthLib->method('processResponse')->willReturn(null);
        $SamlAuthLib->method('getErrors')->willReturn(null);
        $SamlAuthLib->method('getAttributes')->willReturn($samlUserdata);
        $SamlAuthLib->method('isAuthenticated')->willReturn(true);
        $AuthService = new SamlAuth($SamlAuthLib, $config, $settings);
        return $AuthService->assertIdpResponse();
    }
}
