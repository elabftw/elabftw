<?php

declare(strict_types=1);

/**
 * @copyright 2026 Nicolas CARPi / Deltablot
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Codeception\Attribute\DataProvider;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\RequestableAction;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

use function json_encode;
use function sprintf;

class RequestActionsAuthorizationTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Db $Db;

    private Users $requester;

    private Users $target;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->beginTransaction();
        $this->requester = $this->getUserInTeam(1);
        $this->target = $this->getUserInTeam(1, 1);
        $this->assertNotSame($this->requester->userid, $this->target->userid);
    }

    protected function tearDown(): void
    {
        $this->Db->rollBack();
    }

    #[DataProvider('concreteEntityTypes')]
    public function testRejectsTargetWithoutReadAccess(EntityType $type): void
    {
        $Entity = $this->createEntity($type, BasePermissions::UserOnly);
        // The target is an admin in the same team, but only the owner can read this entity.
        $this->assertTrue($this->target->isAdmin);
        $this->assertRejectedWithoutSideEffects($Entity, $this->target->getUserid());
    }

    #[DataProvider('concreteEntityTypes')]
    public function testRejectsOtherTeamWithoutReadAccess(EntityType $type): void
    {
        $target = $this->getUserInTeam(2);
        $this->assertNotContains(1, $target->getActiveTeamIds());
        $Entity = $this->createEntity($type, BasePermissions::Team);
        $this->assertRejectedWithoutSideEffects($Entity, $target->getUserid());
    }

    #[DataProvider('concreteEntityTypes')]
    public function testAllowsOtherTeamWithReadAccess(EntityType $type): void
    {
        $target = $this->getUserInTeam(2);
        foreach (array(BasePermissions::Full, BasePermissions::Organization) as $base) {
            $Entity = $this->createEntity($type, $base);
            $type->toInstance($target, $Entity->id)->canOrExplode(AccessType::Read);
            $Requests = new RequestActions($this->requester, $Entity);
            $this->assertIsInt($Requests->postAction(Action::Create, array(
                'target_userid' => $target->userid,
                'target_action' => RequestableAction::Archive->value,
            )));
        }
    }

    #[DataProvider('concreteEntityTypes')]
    public function testRejectsArchivedTarget(EntityType $type): void
    {
        $Entity = $this->createEntity($type, BasePermissions::Team);
        // Another active membership must not authorize requests in the archived team.
        $this->Db->q(sprintf('INSERT IGNORE INTO users2teams (users_id, teams_id) VALUES (%d, 2)', $this->target->getUserid()));
        $this->Db->q(sprintf('UPDATE users2teams SET is_archived = 1 WHERE users_id = %d AND teams_id = 1', $this->target->getUserid()));
        $this->assertRejectedWithoutSideEffects($Entity, $this->target->getUserid());
    }

    #[DataProvider('concreteEntityTypes')]
    public function testDoesNotReuseRequesterPermissionsOrBypassFlags(EntityType $type): void
    {
        $Entity = $this->createEntity($type, BasePermissions::Team);
        $this->Db->q(sprintf('UPDATE %s SET canread_base = %d WHERE id = %d', $type->value, BasePermissions::UserOnly->value, $Entity->id));
        $Entity->bypassReadPermission = true;
        $Entity->bypassWritePermission = true;
        $this->assertRejectedWithoutSideEffects($Entity, $this->target->getUserid());
    }

    #[DataProvider('concreteEntityTypes')]
    public function testAllowsReadableEntitiesInCurrentTeam(EntityType $type): void
    {
        foreach (array(BasePermissions::Team, BasePermissions::Organization) as $base) {
            $Entity = $this->createEntity($type, $base);
            $Requests = new RequestActions($this->requester, $Entity);
            $id = $Requests->postAction(Action::Create, array(
                'target_userid' => $this->target->userid,
                'target_action' => RequestableAction::Archive->value,
            ));
            $Requests->setId($id);
            $this->assertSame($this->target->userid, $Requests->readOne()['target_userid']);
            $this->assertCount(1, $Requests->readAll());
        }
    }

    #[DataProvider('concreteEntityTypes')]
    public function testAllowsExplicitlySharedEntity(EntityType $type): void
    {
        foreach (array($this->target, $this->getUserInTeam(2)) as $target) {
            $Entity = $type->toInstance($this->requester);
            $id = $Entity->create(
                canreadBase: BasePermissions::UserOnly,
                canwriteBase: BasePermissions::UserOnly,
                canread: json_encode(array('teams' => array(), 'teamgroups' => array(), 'users' => array($target->userid)), JSON_THROW_ON_ERROR),
            );
            $Entity->setId($id);
            $Requests = new RequestActions($this->requester, $Entity);
            $this->assertIsInt($Requests->postAction(Action::Create, array(
                'target_userid' => $target->userid,
                'target_action' => RequestableAction::Review->value,
            )));
        }
    }

    #[DataProvider('concreteEntityTypes')]
    public function testUsesTargetMembershipInEntityTeam(EntityType $type): void
    {
        $this->Db->q(sprintf('INSERT IGNORE INTO users2teams (users_id, teams_id) VALUES (%d, 2)', $this->target->getUserid()));
        $Entity = $type->toInstance($this->getUserInTeam(2));
        $id = $Entity->create(
            canreadBase: BasePermissions::Team,
            canwriteBase: BasePermissions::UserOnly,
            canread: json_encode(array('teams' => array(), 'teamgroups' => array(), 'users' => array($this->requester->userid)), JSON_THROW_ON_ERROR),
        );
        $Entity = $type->toInstance($this->requester, $id);
        $Requests = new RequestActions($this->requester, $Entity);
        $this->assertIsInt($Requests->postAction(Action::Create, array(
            'target_userid' => $this->target->userid,
            'target_action' => RequestableAction::Archive->value,
        )));
    }

    private function createEntity(EntityType $type, BasePermissions $base): AbstractEntity
    {
        $Entity = $type->toInstance($this->requester);
        $Entity->setId($Entity->create(canreadBase: $base, canwriteBase: BasePermissions::UserOnly));
        return $Entity;
    }

    private function assertRejectedWithoutSideEffects(AbstractEntity $Entity, int $targetUserid): void
    {
        $before = $this->sideEffects($Entity);
        try {
            new RequestActions($this->requester, $Entity)->postAction(Action::Create, array(
                'target_userid' => $targetUserid,
                'target_action' => RequestableAction::Archive->value,
            ));
            $this->fail('An unauthorized action request was accepted.');
        } catch (ForbiddenException) {
            $this->assertSame($before, $this->sideEffects($Entity));
        }
    }

    private function sideEffects(AbstractEntity $Entity): array
    {
        return array(
            'requests' => new RequestActions($this->requester, $Entity)->readAll(),
            // Notifications also contain the pending email flag.
            'notifications' => $this->Db->q('SELECT COUNT(*) FROM notifications')->fetchColumn(),
            'audit_logs' => $this->Db->q('SELECT COUNT(*) FROM audit_logs')->fetchColumn(),
            'changelog' => new Changelog($Entity)->readAll(),
        );
    }
}
