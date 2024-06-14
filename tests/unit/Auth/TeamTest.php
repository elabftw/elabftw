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

use Elabftw\Elabftw\AuthResponse;

class TeamTest extends \PHPUnit\Framework\TestCase
{
    public function testTryAuth(): void
    {
        $AuthService = new Team(1, 1);
        $authResponse = $AuthService->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $authResponse);
        $this->assertEquals(1, $authResponse->userid);
        $this->assertFalse($authResponse->isAnonymous);
        $this->assertEquals(1, $authResponse->selectedTeam);
    }

    public function testTryAuthInvalidUser(): void
    {
        $AuthService = new Team(8, 2);

        $authResponse = $AuthService->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $authResponse);
        $this->assertEquals(8, $authResponse->userid);
        $this->assertFalse($authResponse->isValidated);
        $this->assertFalse($authResponse->isAnonymous);
        $this->assertEquals(2, $authResponse->selectedTeam);
    }
}
