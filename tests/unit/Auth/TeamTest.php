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

use Elabftw\Enums\Action;
use Elabftw\Models\Users\UltraAdmin;
use Elabftw\Models\Users\Users;

class TeamTest extends \PHPUnit\Framework\TestCase
{
    public function testTryAuth(): void
    {
        $AuthService = new Team(1, 1);
        $authResponse = $AuthService->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $authResponse);
        $this->assertEquals(1, $authResponse->getAuthUserid());
        $this->assertFalse($authResponse->isAnonymous());
        $this->assertEquals(1, $authResponse->getSelectedTeam());
    }

    public function testTryAuthInvalidUser(): void
    {
        $team = 2;
        $Users = new Users();
        $invalidUserId = $Users->createOne('auth-team-test@example.com', array($team));
        $invalidUser = new Users($invalidUserId, $team, new UltraAdmin());
        $invalidUser->patch(Action::Update, array('validated' => '0'));

        $AuthService = new Team($invalidUserId, $team);

        $authResponse = $AuthService->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $authResponse);
        $this->assertEquals($invalidUserId, $authResponse->getAuthUserid());
        $this->assertFalse($authResponse->isAnonymous());
        $this->assertEquals($team, $authResponse->getSelectedTeam());
    }
}
