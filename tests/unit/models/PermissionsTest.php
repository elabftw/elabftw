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
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Traits\TestsUtilsTrait;

use function json_decode;
use function json_encode;

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
        $alphaExp->patch(Action::Update, array('canread_base' => BasePermissions::Full->value));
        // and check if user in bravo can see it
        $bravoExp = new Experiments($userInBravo);
        $bravoExp->setId($expId);
        $this->assertIsArray($bravoExp->readOne());

        // reduce to organization, should still be visible to bravo user
        $alphaExp->patch(Action::Update, array('canread_base' => BasePermissions::Organization->value));
        $this->assertIsArray($bravoExp->readOne());

        // set base to Team but add bravo team in teams array so it's readable again
        $perm = json_decode(AbstractEntity::EMPTY_CAN_JSON, true);
        $perm['teams'] = array(2);
        $alphaExp->patch(Action::Update, array('canread' => json_encode($perm)));
        $this->assertIsArray($bravoExp->readOne());

        // same but with "users" array
        $perm = json_decode(AbstractEntity::EMPTY_CAN_JSON, true);
        $perm['users'] = array($userInBravo->userid);
        $alphaExp->patch(Action::Update, array('canread' => json_encode($perm)));
        $this->assertIsArray($bravoExp->readOne());

        // reduce to team only, is not readable anymore by user from bravo
        $alphaExp->patch(Action::Update, array('canread_base' => BasePermissions::Team->value));
        // and remove bravo from list of teams
        $perm = json_decode(AbstractEntity::EMPTY_CAN_JSON, true);
        $perm['teams'] = array();
        $alphaExp->patch(Action::Update, array('canread' => json_encode($perm)));
        $this->expectException(ForbiddenException::class);
        $bravoExp->readOne();
    }

    public function testArchivedTeamDoesNotGrantReadAccess(): void
    {
        // viewer logged into team 1
        $viewer = new AuthenticatedUser(2, 1);
        // create exp as another user from team 2
        $owner = $this->getUserInTeam(2);

        // Viewer is active in team 1 but archived from team 2
        $viewer->userData['teams'] = array(
            array('id' => 1, 'is_archived' => 0),
            array('id' => 2, 'is_archived' => 1),
        );

        $experiment = new Experiments($owner);
        $experimentId = $experiment->postAction(Action::Create, array());
        $experiment->setId($experimentId);

        // share the experiment with team 2. before fix, archived team 2 membership
        // was still considered and incorrectly had access
        $canread = json_decode(AbstractEntity::EMPTY_CAN_JSON, true);
        $canread['teams'] = array(2);
        $experiment->patch(Action::Update, array(
            'canread' => json_encode($canread),
        ));

        $viewerExperiment = new Experiments($viewer);

        $this->expectException(ForbiddenException::class);
        $viewerExperiment->setId($experimentId);
    }
}
