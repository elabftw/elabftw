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

use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\QuantumException;

class LocalTest extends \PHPUnit\Framework\TestCase
{
    private Local $AuthService;

    protected function setUp(): void
    {
        $this->AuthService = new Local('toto@yopmail.com', 'totototototo');
    }

    public function testEmptyPassword(): void
    {
        $this->expectException(QuantumException::class);
        new Local('toto@yopmail.com', '');
    }

    public function testTryAuth(): void
    {
        $authResponse = $this->AuthService->tryAuth();
        $this->assertEquals(1, $authResponse->userid);
        $this->assertEquals(1, $authResponse->selectedTeam);
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

    public function testIsMfaEnforced(): void
    {
        $this->assertTrue($this->AuthService::isMfaEnforced(1, 3));
        $this->assertTrue($this->AuthService::isMfaEnforced(1, 1));
        $this->assertFalse($this->AuthService::isMfaEnforced(4, 1));
        $this->assertTrue($this->AuthService::isMfaEnforced(5, 2));
        $this->assertFalse($this->AuthService::isMfaEnforced(4, 0));
    }
}
