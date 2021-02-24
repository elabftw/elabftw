<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Elabftw\Saml;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Config;
use Elabftw\Models\Idps;
use OneLogin\Saml2\Auth as SamlAuthLib;

class SamlAuthTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->configArr = array(
            'debug' => '0',
            'saml_sync_teams' => '0',
            'saml_email' => 'User.email',
            'saml_team' => 'User.team',
            'saml_team_default' => '2',
            'saml_firstname' => 'User.firstname',
            'saml_lastname' => 'User.lastname',
        );

        // don't use the real saml lib but create a mock
        $this->SamlAuthLib = $this->createMock(SamlAuthLib::class);
        $this->SamlAuthLib->method('login')->willReturn(null);
        $this->SamlAuthLib->method('processResponse')->willReturn(null);
        $this->SamlAuthLib->method('getErrors')->willReturn(null);
        $this->SamlAuthLib->method('isAuthenticated')->willReturn(true);

        // create fake saml idp response
        $this->samlUserdata = array();
        $this->samlUserdata['User.email'] = 'phpunit@example.com';
        $this->samlUserdata['User.firstname'] = 'Phpunit';
        $this->samlUserdata['User.lastname'] = 'FTW';
        $this->samlUserdata['User.team'] = 'Alpha';
        $this->SamlAuthLib->method('getAttributes')->willReturn($this->samlUserdata);

        $Saml = new Saml(new Config(), new Idps());
        $idpId = 1;
        $this->settings = $Saml->getSettings($idpId);
    }

    public function testTryAuth()
    {
        $AuthService = new SamlAuth($this->SamlAuthLib, $this->configArr, $this->settings);
        $authResponse = $AuthService->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $authResponse);
        $this->assertEquals('saml', $authResponse->isAuthBy);
    }

    public function testAssertIdpResponse()
    {
        // happy path
        $AuthService = new SamlAuth($this->SamlAuthLib, $this->configArr, $this->settings);
        $authResponse = $AuthService->assertIdpResponse();
        $this->assertInstanceOf(AuthResponse::class, $authResponse);
        $this->assertEquals('saml', $authResponse->isAuthBy);
        $this->assertEquals(1, $authResponse->userid);
        $this->assertFalse($authResponse->isAnonymous);
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    public function testAssertIdpResponseSyncTeams()
    {
        $configArr = $this->configArr;
        $configArr['saml_sync_teams'] = '1';
        $AuthService = new SamlAuth($this->SamlAuthLib, $configArr, $this->settings);
        $authResponse = $AuthService->assertIdpResponse();
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    public function testAssertIdpResponseFailedAuth()
    {
        // now try with a failed auth
        // don't use the real saml lib but create a mock
        $this->SamlAuthLib = $this->createMock(SamlAuthLib::class);
        $this->SamlAuthLib->method('login')->willReturn(null);
        $this->SamlAuthLib->method('processResponse')->willReturn(null);
        $this->SamlAuthLib->method('getErrors')->willReturn(null);
        // FIXME do I really need to remake the mock entirely?
        // calling just the line below doesn't work
        $this->SamlAuthLib->method('isAuthenticated')->willReturn(false);
        $AuthService = new SamlAuth($this->SamlAuthLib, $this->configArr, $this->settings);
        $this->expectException(UnauthorizedException::class);
        $authResponse = $AuthService->assertIdpResponse();
    }

    /**
     * Idp doesn't send back a team
     */
    public function testAssertIdpResponseNoTeamResponse()
    {
        $samlUserdata = $this->samlUserdata;
        unset($samlUserdata['User.team']);
        $SamlAuthLib = $this->createMock(SamlAuthLib::class);
        $SamlAuthLib->method('login')->willReturn(null);
        $SamlAuthLib->method('processResponse')->willReturn(null);
        $SamlAuthLib->method('getErrors')->willReturn(null);
        $SamlAuthLib->method('getAttributes')->willReturn($samlUserdata);
        $SamlAuthLib->method('isAuthenticated')->willReturn(true);
        $AuthService = new SamlAuth($SamlAuthLib, $this->configArr, $this->settings);
        $authResponse = $AuthService->assertIdpResponse();
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    /**
     * Idp doesn't send back a team and there are no default team
     */
    public function testAssertIdpResponseNoTeamResponseNoDefaultTeam()
    {
        $samlUserdata = $this->samlUserdata;
        unset($samlUserdata['User.team']);
        $SamlAuthLib = $this->createMock(SamlAuthLib::class);
        $SamlAuthLib->method('login')->willReturn(null);
        $SamlAuthLib->method('processResponse')->willReturn(null);
        $SamlAuthLib->method('getErrors')->willReturn(null);
        $SamlAuthLib->method('getAttributes')->willReturn($samlUserdata);
        $SamlAuthLib->method('isAuthenticated')->willReturn(true);
        // same but with no configured default team
        $configArr = $this->configArr;
        $configArr['saml_team_default'] = '0';
        $AuthService = new SamlAuth($SamlAuthLib, $configArr, $this->settings);
        $authResponse = $AuthService->assertIdpResponse();
        // as user exists already, they'll be in team 1
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    /**
     * Idp sends an array of teams
     */
    public function testAssertIdpResponseTeamsArrayResponse()
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.team'] = array('Alpha');
        $SamlAuthLib = $this->createMock(SamlAuthLib::class);
        $SamlAuthLib->method('login')->willReturn(null);
        $SamlAuthLib->method('processResponse')->willReturn(null);
        $SamlAuthLib->method('getErrors')->willReturn(null);
        $SamlAuthLib->method('getAttributes')->willReturn($samlUserdata);
        $SamlAuthLib->method('isAuthenticated')->willReturn(true);
        $AuthService = new SamlAuth($SamlAuthLib, $this->configArr, $this->settings);
        $authResponse = $AuthService->assertIdpResponse();
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    /**
     * Idp sends an array of email
     */
    public function testAssertIdpResponseEmailArrayResponse()
    {
        $samlUserdata = $this->samlUserdata;
        $samlUserdata['User.email'] = array('phpunit@example.com');
        $SamlAuthLib = $this->createMock(SamlAuthLib::class);
        $SamlAuthLib->method('login')->willReturn(null);
        $SamlAuthLib->method('processResponse')->willReturn(null);
        $SamlAuthLib->method('getErrors')->willReturn(null);
        $SamlAuthLib->method('getAttributes')->willReturn($samlUserdata);
        $SamlAuthLib->method('isAuthenticated')->willReturn(true);
        $AuthService = new SamlAuth($SamlAuthLib, $this->configArr, $this->settings);
        $authResponse = $AuthService->assertIdpResponse();
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    /**
     * Idp doesn't send back an email
     */
    public function testAssertIdpResponseNoEmail()
    {
        $samlUserdata = $this->samlUserdata;
        unset($samlUserdata['User.email']);
        $SamlAuthLib = $this->createMock(SamlAuthLib::class);
        $SamlAuthLib->method('login')->willReturn(null);
        $SamlAuthLib->method('processResponse')->willReturn(null);
        $SamlAuthLib->method('getErrors')->willReturn(null);
        $SamlAuthLib->method('getAttributes')->willReturn($samlUserdata);
        $SamlAuthLib->method('isAuthenticated')->willReturn(true);
        $AuthService = new SamlAuth($SamlAuthLib, $this->configArr, $this->settings);
        $this->expectException(ImproperActionException::class);
        $authResponse = $AuthService->assertIdpResponse();
    }

    /**
     * Try with errors in the response
     */
    public function testAssertIdpResponseError()
    {
        $this->SamlAuthLib = $this->createMock(SamlAuthLib::class);
        // FIXME do I really need to remake the mock entirely?
        // calling just the line below doesn't work
        $this->SamlAuthLib->method('getErrors')->willReturn(array('Error' => 'Something went wrong!'));
        $AuthService = new SamlAuth($this->SamlAuthLib, $this->configArr, $this->settings);
        $this->expectException(UnauthorizedException::class);
        $authResponse = $AuthService->assertIdpResponse();
    }

    /**
     * With debug mode on and errors
     */
    public function testAssertIdpResponseErrorDebug()
    {
        $this->SamlAuthLib = $this->createMock(SamlAuthLib::class);
        // FIXME do I really need to remake the mock entirely?
        // calling just the line below doesn't work
        $this->SamlAuthLib->method('getErrors')->willReturn(array('Error' => 'Something went wrong!'));
        $configArr = $this->configArr;
        $configArr['debug'] = '1';
        $AuthService = new SamlAuth($this->SamlAuthLib, $configArr, $this->settings);
        $this->expectException(UnauthorizedException::class);
        $authResponse = $AuthService->assertIdpResponse();
    }
}
