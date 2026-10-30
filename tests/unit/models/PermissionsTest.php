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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Services\TeamsHelper;
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

    public function testAdminOfAnotherTeamCannotWriteLockedEntity(): void
    {
        $Experiments = $this->getExperimentWritableByAdminOfAnotherTeam(locked: true);
        $this->expectException(ForbiddenException::class);
        $Experiments->patch(Action::Update, array('title' => 'Modified by an admin of another team'));
    }

    public function testAdminOfAnotherTeamCannotUnlockEntity(): void
    {
        $Experiments = $this->getExperimentWritableByAdminOfAnotherTeam(locked: true);
        $this->expectException(ForbiddenException::class);
        $Experiments->patch(Action::ForceUnlock, array());
    }

    public function testAdminOfAnotherTeamCannotToggleLock(): void
    {
        $Experiments = $this->getExperimentWritableByAdminOfAnotherTeam(locked: true);
        // call it directly so the lock check is not done by the write permission check of patch()
        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage("You don't have the rights to lock/unlock this.");
        $Experiments->toggleLock();
    }

    public function testAdminOfAnotherTeamCannotOverrideImmutablePermissions(): void
    {
        $Experiments = $this->getExperimentWritableByAdminOfAnotherTeam(locked: false);
        $this->expectException(UnprocessableContentException::class);
        $this->expectExceptionMessage('Cannot modify permissions on entry with immutable permissions.');
        $Experiments->patch(Action::Update, array('canwrite_base' => BasePermissions::Organization->value));
    }

    public function testAdminOfEntityTeamCanUnlockAndOverrideImmutablePermissions(): void
    {
        $Experiments = $this->getExperimentWritableByAdminOfAnotherTeam(locked: true);
        $admin = $this->getUserInTeam(team: 1, admin: 1);
        // make sure this goes through the Admin check, not the locker one
        $this->assertNotSame($Experiments->entityData['lockedby'], $admin->userid);
        $AdminExperiments = new Experiments($admin, $Experiments->id);

        $exp = $AdminExperiments->patch(Action::Update, array('title' => 'Modified by an admin of the team'));
        $this->assertSame('Modified by an admin of the team', $exp['title']);
        $exp = $AdminExperiments->patch(Action::ForceUnlock, array());
        $this->assertSame(0, $exp['locked']);
        $exp = $AdminExperiments->patch(Action::Update, array('canwrite_base' => BasePermissions::Organization->value));
        $this->assertSame(BasePermissions::Organization->value, $exp['canwrite_base']);
    }

    /**
     * Get an experiment from team 1, with immutable write permissions, shared for reading and writing
     * with a user that is Admin in team 2 but not in team 1. It is loaded with that user, logged in team 2.
     */
    private function getExperimentWritableByAdminOfAnotherTeam(bool $locked): Experiments
    {
        $adminOfOtherTeam = $this->getUserInTeam(team: 2, admin: 1);
        $this->assertTrue($adminOfOtherTeam->isAdmin);
        $this->assertFalse(new TeamsHelper(1)->isAdminInTeam($adminOfOtherTeam->getUserid()));

        $can = json_decode(AbstractEntity::EMPTY_CAN_JSON, true);
        $can['users'] = array($adminOfOtherTeam->getUserid());
        $OwnerExperiments = new Experiments(new AuthenticatedUser(2, 1));
        $id = $OwnerExperiments->create(
            canread: json_encode($can, JSON_THROW_ON_ERROR),
            canwrite: json_encode($can, JSON_THROW_ON_ERROR),
            canwriteIsImmutable: true,
        );
        $OwnerExperiments->setId($id);
        if ($locked) {
            $OwnerExperiments->lock();
        }
        return new Experiments($adminOfOtherTeam, $id);
    }
}
