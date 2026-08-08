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
        $Local->authenticate();
    }

    public function testOnlySysadmin(): void
    {
        $user = $this->getRandomUserInTeam(2);
        $Local = new Local($user->userData['email'], 'notimportant', isOnlySysadmin: true);
        $this->expectException(ImproperActionException::class);
        $Local->authenticate();
    }

    public function testEmptyPassword(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        new Local('toto@yopmail.com', '');
    }

    public function testTryAuth(): void
    {
        $authResponse = $this->AuthService->authenticate();
        $this->assertSame(1, $authResponse->userid);
    }

    public function testTryAuthWithInvalidEmail(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        new Local('invalid@example.com', 'nopenope');
    }

    public function testTryAuthWithInvalidPassword(): void
    {
        $AuthService = new Local('toto@yopmail.com', 'nopenope');
        $this->expectException(InvalidCredentialsException::class);
        $AuthService->authenticate();
    }

    public function testBruteForce(): void
    {
        $user = $this->getRandomUserInTeam(4);
        $Local = new Local($user->userData['email'], 'thisisnotthecorrectpassword', maxLoginAttempts: -1);
        $this->expectException(ImproperActionException::class);
        $Local->authenticate();
    }
}
