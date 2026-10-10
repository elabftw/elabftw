<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Codeception\Attribute\DataProvider;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Models\Users\Users;

use function array_column;
use function json_decode;
use function json_encode;

class UnfinishedStepsTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    private Db $Db;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->beginTransaction();
        $this->Users = new Users(1, 1);
    }

    protected function tearDown(): void
    {
        $this->Db->rollBack();
    }

    public static function entityTypes(): array
    {
        return array(
            'experiments' => array(EntityType::Experiments),
            'resources' => array(EntityType::Items),
        );
    }

    public function testReadStepsUser(): void
    {
        $this->assertIsArray((new UnfinishedSteps($this->Users))->readAll());
    }

    public function testReadStepsTeam(): void
    {
        $this->assertIsArray((new UnfinishedSteps($this->Users, true))->readAll());
    }

    #[DataProvider('entityTypes')]
    public function testArchivedTeamMembershipDoesNotGrantReadAccess(EntityType $model): void
    {
        $this->Db->q('INSERT IGNORE INTO users2teams (users_id, teams_id) VALUES (2, 2)');
        $this->Db->q('UPDATE users2teams SET is_archived = 0 WHERE users_id = 2 AND teams_id = 2');
        // UserOnly isolates the explicit team share from the owner/admin base permission.
        $Entity = $this->createEntityWithUnfinishedStep($model, BasePermissions::UserOnly, array('teams' => array(2)));
        $viewer = new Users(2, 1);
        $this->assertFalse($viewer->isAdmin);
        $this->assertContains($Entity->id, $this->unfinishedEntityIds($viewer, $model));

        $this->Db->q('UPDATE users2teams SET is_archived = 1 WHERE users_id = 2 AND teams_id = 2');
        // Reload the user so the permission builder sees the archived membership.
        $viewer = new Users(2, 1);
        $this->assertNotContains($Entity->id, $this->unfinishedEntityIds($viewer, $model));
        $this->assertNotContains($Entity->id, $this->unfinishedEntityIds($viewer, $model, false));

        $this->expectException(ForbiddenException::class);
        $model->toInstance($viewer, $Entity->id);
    }

    #[DataProvider('entityTypes')]
    public function testTeamScopeChecksOwnerAndAdmin(EntityType $model): void
    {
        $viewer = new Users(2, 1);
        $this->assertFalse($viewer->isAdmin);
        $entities = array();
        foreach (array(BasePermissions::User, BasePermissions::UserOnly) as $base) {
            // Also cover legacy JSON containing a base key: it must not bypass ownership checks.
            foreach (array(array('base' => $base->value), array()) as $canread) {
                $Entity = $this->createEntityWithUnfinishedStep($model, $base, $canread);
                $this->assertNotContains($Entity->id, $this->unfinishedEntityIds($viewer, $model));
                $this->assertContains($Entity->id, $this->unfinishedEntityIds($this->Users, $model));
                $this->assertContains($Entity->id, $this->unfinishedEntityIds($this->Users, $model, false));
                $entities[] = array($Entity, $base);
            }
        }

        $this->Db->q('UPDATE users2teams SET is_admin = 1 WHERE users_id = 2 AND teams_id = 1');
        $admin = new Users(2, 1);
        $this->assertTrue($admin->isAdmin);
        $visibleIds = $this->unfinishedEntityIds($admin, $model);
        foreach ($entities as [$Entity, $base]) {
            if ($base === BasePermissions::User) {
                $this->assertContains($Entity->id, $visibleIds);
            } else {
                $this->assertNotContains($Entity->id, $visibleIds);
            }
        }
    }

    #[DataProvider('entityTypes')]
    public function testTeamScopeAllowsExplicitShares(EntityType $model): void
    {
        $viewer = new Users(2, 1);
        $this->assertFalse($viewer->isAdmin);
        $TeamGroups = new TeamGroups($this->Users);
        $groupId = $TeamGroups->create('Unfinished steps permission test');
        $TeamGroups->setId($groupId);
        $TeamGroups->updateMember(array('how' => Action::Add->value, 'userid' => 2));

        foreach (array(
            array('teams' => array(1)),
            array('teamgroups' => array($groupId)),
            array('users' => array(2)),
        ) as $canread) {
            $Entity = $this->createEntityWithUnfinishedStep($model, BasePermissions::UserOnly, $canread);
            $this->assertContains($Entity->id, $this->unfinishedEntityIds($viewer, $model));
        }
    }

    #[DataProvider('entityTypes')]
    public function testTeamScopeUsesCanreadBaseColumn(EntityType $model): void
    {
        $viewer = new Users(2, 1);
        foreach (array(BasePermissions::Full, BasePermissions::Organization, BasePermissions::Team) as $base) {
            $Entity = $this->createEntityWithUnfinishedStep($model, $base);
            $this->assertContains($Entity->id, $this->unfinishedEntityIds($viewer, $model));
        }
    }

    private function createEntityWithUnfinishedStep(EntityType $model, BasePermissions $base, array $canread = array()): AbstractEntity
    {
        $Entity = $model->toInstance($this->Users);
        $id = $Entity->create(
            title: 'Unfinished steps permission test',
            canreadBase: $base,
            canread: json_encode($canread + json_decode(AbstractEntity::EMPTY_CAN_JSON, true), JSON_THROW_ON_ERROR),
        );
        $Entity->setId($id);
        new Steps($Entity)->postAction(Action::Create, array('body' => 'Private step body'));
        return $Entity;
    }

    private function unfinishedEntityIds(Users $viewer, EntityType $model, bool $teamScoped = true): array
    {
        return array_column(new UnfinishedSteps($viewer, $teamScoped)->readAll()[$model->value], 'id');
    }
}
