<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Traits\TestsUtilsTrait;

class PermissionsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testReadAccessSimple(): void
    {
        $userInAlpha = new AuthenticatedUser(2, 1);
        $userInBravo = $this->getUserInTeam(2);
        $alphaExp = new Experiments($userInAlpha);
        $expId = $alphaExp->postAction(Action::Create, array());
        $alphaExp->setId($expId);
        // start by giving it wide permissions
        $alphaExp->patch(Action::Update, array('canread' => BasePermissions::Full->toJson()));
        // and check if user in bravo can see it
        $bravoExp = new Experiments($userInBravo);
        $bravoExp->setId($expId);
        $this->assertIsArray($bravoExp->readOne());

        // reduce to organization, should still be visible to bravo user
        $alphaExp->patch(Action::Update, array('canread' => BasePermissions::Organization->toJson()));
        $this->assertIsArray($bravoExp->readOne());

        // set base to Team but add bravo team in teams array so it's readable again
        $perm = json_decode(BasePermissions::Team->toJson(), true);
        $perm['teams'] = array(2);
        $alphaExp->patch(Action::Update, array('canread' => json_encode($perm)));
        $this->assertIsArray($bravoExp->readOne());

        // same but with "users" array
        $perm = json_decode(BasePermissions::Team->toJson(), true);
        $perm['users'] = array($userInBravo->userid);
        $alphaExp->patch(Action::Update, array('canread' => json_encode($perm)));
        $this->assertIsArray($bravoExp->readOne());

        // reduce to team only, is not readable anymore by user from bravo
        $alphaExp->patch(Action::Update, array('canread' => BasePermissions::Team->toJson()));
        $this->expectException(UnauthorizedException::class);
        $bravoExp->readOne();
    }
}
