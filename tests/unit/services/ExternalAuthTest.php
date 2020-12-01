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
use Elabftw\Exceptions\ImproperActionException;
use Monolog\Logger;

class ExternalAuthTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->configArr = array(
            'extauth_firstname' => 'auth_firstname',
            'extauth_lastname' => 'auth_lastname',
            'extauth_email' => 'auth_email',
            'extauth_teams' => 'auth_team',
            'saml_team_default' => 1,
        );
        $this->serverParams = array(
            'auth_firstname' => 'Phpunit',
            'auth_lastname' => 'FTW',
            'auth_email' => 'phpunit@example.com',
            'auth_team' => 'Alpha',
        );
        $this->log = new Logger('elabftw');
        $this->ExternalAuth = new ExternalAuth(
            $this->configArr,
            $this->serverParams,
            $this->log,
        );
    }

    public function testTryAuth()
    {
        $authResponse = $this->ExternalAuth->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $authResponse);
        $this->assertEquals('external', $authResponse->isAuthBy);
        $this->assertEquals(1, $authResponse->userid);
        $this->assertFalse($authResponse->isAnonymous);
        $this->assertEquals(1, $authResponse->selectedTeam);
        $teams = array(array('id' => '1', 'name' => 'Alpha'));
        $this->assertEquals($teams, $authResponse->selectableTeams);
    }

    // now try with a non existing user
    public function testTryAuthWithNonExistingUser()
    {
        $serverParams = $this->serverParams;
        $serverParams['auth_email'] = 'nonexisting@yopmail.com';
        $ExternalAuth = new ExternalAuth(
            $this->configArr,
            $serverParams,
            $this->log,
        );
        $authResponse = $ExternalAuth->tryAuth();
        $this->assertEquals(8, $authResponse->userid);
    }

    // now try without a team sent by server
    public function testTryAuthWithoutTeamSentByServer()
    {
        // make sure we use the default team
        $this->serverParams['auth_team'] = null;
        $ExternalAuth = new ExternalAuth(
            $this->configArr,
            $this->serverParams,
            $this->log,
        );
        $authResponse = $ExternalAuth->tryAuth();
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    // now try with throwing exception if no team is found
    public function testTryAuthWithoutTeamGetException()
    {
        // because sysadmin configured it like that
        $this->configArr['saml_team_default'] = 0;
        $this->serverParams['auth_team'] = null;
        $ExternalAuth = new ExternalAuth(
            $this->configArr,
            $this->serverParams,
            $this->log,
        );
        $this->expectException(ImproperActionException::class);
        $authResponse = $ExternalAuth->tryAuth();
    }
}
