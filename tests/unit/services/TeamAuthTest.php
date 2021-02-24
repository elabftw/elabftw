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

class TeamAuthTest extends \PHPUnit\Framework\TestCase
{
    public function testTryAuth()
    {
        $AuthService = new TeamAuth(1, 1);
        $authResponse = $AuthService->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $authResponse);
        $this->assertEquals('team', $authResponse->isAuthBy);
        $this->assertEquals(1, $authResponse->userid);
        $this->assertFalse($authResponse->isAnonymous);
        $this->assertEquals(1, $authResponse->selectedTeam);
    }
}
