<?php

declare(strict_types=1);

namespace Elabftw\Models;

use Codeception\Attribute\DataProvider;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use PHPUnit\Framework\TestCase;

use function sprintf;

final class OwnershipTransferTest extends TestCase
{
    use TestsUtilsTrait;

    private Db $Db;

    private Users $owner;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->beginTransaction();
        $this->owner = $this->getUserInTeam(1);
        self::assertFalse($this->owner->isAdmin);
        self::assertFalse($this->owner->isSysadmin());
    }

    protected function tearDown(): void
    {
        $this->Db->rollBack();
    }

    #[DataProvider('concreteEntityTypes')]
    public function testOwnerCanTransferOwnerOnlyEntryWithinTeam(EntityType $type): void
    {
        $target = $this->getUserInTeam(1, 1);
        $entity = $this->createEntity($type, $this->owner, BasePermissions::UserOnly);
        $entity->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => 1));
        // The old owner can lose read access; verify through the new owner.
        $after = $type->toInstance($target, $entity->id)->readOne();
        self::assertSame($target->getUserid(), (int) $after['userid']);
        self::assertSame(1, (int) $after['team']);
    }

    #[DataProvider('concreteEntityTypes')]
    public function testWritePermissionDoesNotGrantOwnershipTransfer(EntityType $type): void
    {
        $entity = $this->createEntity($type, $this->getUserInTeam(1, 1));
        $shared = $type->toInstance($this->owner, $entity->id);
        $this->expectException(ForbiddenException::class);
        $shared->patch(Action::UpdateOwner, array('userid' => $this->owner->getUserid(), 'team' => 1));
    }

    #[DataProvider('concreteEntityTypes')]
    public function testOwnerCannotTransferAcrossTeams(EntityType $type): void
    {
        $entity = $this->createEntity($type, $this->owner);
        $target = $this->getUserInTeam(2);
        $this->expectException(ForbiddenException::class);
        $entity->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => 2));
    }

    #[DataProvider('concreteEntityTypes')]
    public function testAdminOfOtherTeamCannotMoveOwnedEntryIntoThatTeam(EntityType $type): void
    {
        $entity = $this->createEntity($type, $this->owner);
        $userid = $this->owner->getUserid();
        $this->Db->q(sprintf('INSERT IGNORE INTO users2teams (users_id, teams_id) VALUES (%d, 2)', $userid));
        $this->Db->q(sprintf('UPDATE users2teams SET is_admin = 1 WHERE users_id = %d AND teams_id = 2', $userid));
        $otherTeamAdmin = new Users($userid, 2);
        self::assertTrue($otherTeamAdmin->isAdmin);
        $shared = $type->toInstance($otherTeamAdmin, $entity->id);
        $this->expectException(ForbiddenException::class);
        $shared->patch(Action::UpdateOwner, array('userid' => $userid, 'team' => 2));
    }

    #[DataProvider('concreteEntityTypes')]
    public function testTargetMustBelongToTheDestinationTeam(EntityType $type): void
    {
        $entity = $this->createEntity($type, $this->owner);
        $target = $this->getUserInTeam(2);
        self::assertNotContains(1, $target->getActiveTeamIds());
        $this->expectException(UnprocessableContentException::class);
        $entity->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => 1));
    }

    private function createEntity(EntityType $type, Users $owner, BasePermissions $base = BasePermissions::Organization): AbstractEntity
    {
        $entity = $type->toInstance($owner);
        $id = $entity->create(canreadBase: $base, canwriteBase: $base);
        $entity->setId($id);
        return $entity;
    }
}
