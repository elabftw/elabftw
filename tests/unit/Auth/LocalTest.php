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

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\QuantumException;
use Elabftw\Traits\TestsUtilsTrait;

class LocalTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Local $AuthService;

    protected function setUp(): void
    {
        $this->AuthService = new Local('toto@yopmail.com', 'totototototo');
    }

    public function testOnlySysadminWhenHidden(): void
    {
        $user = $this->getRandomUserInTeam(2);
        $Local = new Local($user->userData['email'], 'notimportant', isDisplayed: false, isOnlySysadminWhenHidden: true);
        $this->expectException(IllegalActionException::class);
        $Local->tryAuth();
    }

    public function testOnlySysadmin(): void
    {
        $user = $this->getRandomUserInTeam(2);
        $Local = new Local($user->userData['email'], 'notimportant', isOnlySysadmin: true);
        $this->expectException(ImproperActionException::class);
        $Local->tryAuth();
    }

    public function testEmptyPassword(): void
    {
        $this->expectException(QuantumException::class);
        new Local('toto@yopmail.com', '');
    }

    public function testTryAuth(): void
    {
        $authResponse = $this->AuthService->tryAuth();
        $this->assertEquals(1, $authResponse->getAuthUserid());
        $this->assertEquals(1, $authResponse->getSelectedTeam());
    }

    public function testTryAuthWithInvalidEmail(): void
    {
        $this->expectException(QuantumException::class);
        new Local('invalid@example.com', 'nopenope');
    }

    public function testTryAuthWithInvalidPassword(): void
    {
        $AuthService = new Local('toto@yopmail.com', 'nopenope');
        $this->expectException(InvalidCredentialsException::class);
        $AuthService->tryAuth();
    }

    /*
    public function testIsMfaEnforced(): void
    {
        $this->assertTrue($this->AuthService::isMfaEnforced(1, 3));
        $this->assertTrue($this->AuthService::isMfaEnforced(1, 1));
        $this->assertFalse($this->AuthService::isMfaEnforced(4, 1));
        $admin2 = $this->getUserInTeam(team: 2, admin: 1);
        $this->assertTrue($this->AuthService::isMfaEnforced($admin2->userid, 2));
        $this->assertFalse($this->AuthService::isMfaEnforced(4, 0));
    }
     */

    public function testBruteForce(): void
    {
        $user = $this->getRandomUserInTeam(4);
        $Local = new Local($user->userData['email'], 'thisisnotthecorrectpassword', maxLoginAttempts: -1);
        $this->expectException(ImproperActionException::class);
        $Local->tryAuth();
    }
}
