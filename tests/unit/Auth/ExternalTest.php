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
use Elabftw\Interfaces\AuthResponseInterface;

class ExternalTest extends \PHPUnit\Framework\TestCase
{
    private array $configArr;

    private array $serverParams;

    private External $External;

    protected function setUp(): void
    {
        $this->configArr = array(
            'extauth_firstname' => 'auth_firstname',
            'extauth_lastname' => 'auth_lastname',
            'extauth_email' => 'auth_email',
            'extauth_teams' => 'auth_team',
            'saml_team_default' => '1',
            'saml_user_default' => '1',
            'user_msg_need_local_account_created' => 'yep',
        );
        $this->serverParams = array(
            'auth_firstname' => 'Toto',
            'auth_lastname' => 'Le sysadmin',
            'auth_email' => 'toto@yopmail.com',
            'auth_team' => 'Alpha',
        );
        $this->External = new External(
            $this->configArr,
            $this->serverParams,
        );
    }

    public function testTryAuth(): void
    {
        $authResponse = $this->External->tryAuth();
        $this->assertInstanceOf(AuthResponseInterface::class, $authResponse);
        $this->assertSame(1, $authResponse->getAuthUserid());
        $this->assertFalse($authResponse->isAnonymous());
        $this->assertSame(1, $authResponse->getSelectedTeam());
        $teams = array(array('id' => 1, 'name' => 'Alpha', 'is_admin' => 1, 'is_owner' => 0, 'is_archived' => 0));
        $this->assertSame($teams, $authResponse->getSelectableTeams());
    }

    // now try with a non existing user
    // user will be created of the fly
    public function testTryAuthWithNonExistingUser(): void
    {
        $serverParams = $this->serverParams;
        $serverParams['auth_email'] = 'nonexisting@yopmail.com';
        $External = new External(
            $this->configArr,
            $serverParams,
        );
        $authResponse = $External->tryAuth();
        $this->assertIsInt($authResponse->getAuthUserid());
    }

    // now try with a non existing user and config is set to not create the user
    public function testTryAuthWithNonExistingUserNoCreate(): void
    {
        $serverParams = $this->serverParams;
        $serverParams['auth_email'] = 'nonexisting2@yopmail.com';
        $configArr = $this->configArr;
        $configArr['saml_user_default'] = '0';
        $External = new External(
            $configArr,
            $serverParams,
        );
        $this->expectException(ImproperActionException::class);
        $External->tryAuth();
    }

    // now try without a team sent by server
    public function testTryAuthWithoutTeamSentByServer(): void
    {
        // make sure we use the default team
        $this->serverParams['auth_team'] = null;
        $External = new External(
            $this->configArr,
            $this->serverParams,
        );
        $authResponse = $External->tryAuth();
        $this->assertSame(1, $authResponse->getSelectedTeam());
    }

    // now try with throwing exception if no team is found
    public function testTryAuthWithoutTeamGetException(): void
    {
        // because sysadmin configured it like that
        $this->configArr['saml_team_default'] = 0;
        $this->serverParams['auth_team'] = null;
        $External = new External(
            $this->configArr,
            $this->serverParams,
        );
        $this->expectException(ImproperActionException::class);
        $External->tryAuth();
    }
}
