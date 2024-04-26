<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Services\UsersHelper;

class AuthResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testTryButUserHasNoTeam(): void
    {
        $AuthResponse = new AuthResponse();
        $AuthResponse->userid = 1;
        // no team
        $UsersHelperStub = $this->createStub(UsersHelper::class);
        $UsersHelperStub->method('getTeamsFromUserid')->willReturn(array());
        $AuthResponse->setTeams($UsersHelperStub);
        $this->assertTrue($AuthResponse->teamSelectionRequired);
        // one team
        $UsersHelperStub = $this->createStub(UsersHelper::class);
        $UsersHelperStub->method('getTeamsFromUserid')->willReturn(array(array('id' => 1)));
        $AuthResponse->setTeams($UsersHelperStub);
        $this->assertEquals(1, $AuthResponse->selectedTeam);
        // more than 1 team
        $UsersHelperStub = $this->createStub(UsersHelper::class);
        $UsersHelperStub->method('getTeamsFromUserid')->willReturn(array(array('id' => 3), array('id' => 1)));
        $AuthResponse->setTeams($UsersHelperStub);
        $this->assertTrue($AuthResponse->isInSeveralTeams);
    }
}
