<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\FileFromString;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;
use PHPUnit\Framework\TestCase;

use function sprintf;

/**
 * Authorization rules of Action::UpdateOwner, for every entity type sharing AbstractEntity::updateOwnership()
 *
 * Roles are set up explicitly inside a rolled-back transaction instead of relying on fixture data,
 * so every test knows exactly which flags (owner, admin, sysadmin, archived) the actors have.
 */
final class OwnershipTransferTest extends TestCase
{
    use TestsUtilsTrait;

    private const int SOURCE_TEAM = 1;

    private const int OTHER_TEAM = 2;

    private Db $Db;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->Db->rollBack();
    }

    /**
     * Owner logged in another team than the entry's team, with or without admin rights there, aiming at either team
     */
    public static function ownerInOtherTeamContext(): array
    {
        $cases = array();
        foreach (self::entityTypes() as [$type]) {
            $cases[$type->value . ', member there, within entry team'] = array($type, 0, self::SOURCE_TEAM);
            $cases[$type->value . ', admin there, within entry team'] = array($type, 1, self::SOURCE_TEAM);
            $cases[$type->value . ', admin there, into that team'] = array($type, 1, self::OTHER_TEAM);
        }
        return $cases;
    }

    /**
     * Who gets the transfer button, which mirrors the first authorization step of the transfer
     */
    public static function transferButtonCases(): array
    {
        $roles = array(
            'owner in entry team' => true,
            'owner logged in another team' => false,
            'writer of entry team' => false,
            'admin of entry team' => true,
            'admin of another team' => false,
            'sysadmin' => true,
            'anonymous reader' => false,
        );
        $cases = array();
        foreach (self::entityTypes() as [$type]) {
            foreach ($roles as $role => $expected) {
                $cases[$type->value . ', ' . $role] = array($type, $role, $expected);
            }
        }
        return $cases;
    }

    #[DataProvider('transferButtonCases')]
    public function testCanTransferOwnership(EntityType $type, string $role, bool $expected): void
    {
        $owner = $this->getRegularUserInTeam(self::SOURCE_TEAM);
        $excluded = array($owner->getUserid());
        // visible to everyone, anonymous included, so every role can load the entry
        $entity = $this->getFreshEntityWithGivenUser($type, $owner, BasePermissions::Full);
        $requester = match ($role) {
            'owner in entry team' => $owner,
            'owner logged in another team' => $this->ownerLoggedInOtherTeam($owner, isAdminThere: 1),
            'writer of entry team' => $this->getRegularUserInTeam(self::SOURCE_TEAM, $excluded),
            'admin of entry team' => $this->getFreshAdminInTeam(self::SOURCE_TEAM, $excluded),
            'admin of another team' => $this->getFreshAdminInTeam(self::OTHER_TEAM, $excluded),
            'sysadmin' => $this->getFreshSysadminInTeam(self::SOURCE_TEAM, $excluded),
            'anonymous reader' => new AnonymousUser(self::SOURCE_TEAM),
            default => self::fail(sprintf('Unknown role: %s', $role)),
        };

        self::assertSame($expected, $type->toInstance($requester, $entity->id)->canTransferOwnership());
    }

    #[DataProvider('entityTypes')]
    public function testOwnerCanTransferWithinTeam(EntityType $type): void
    {
        $owner = $this->getRegularUserInTeam(self::SOURCE_TEAM);
        $target = $this->getRegularUserInTeam(self::SOURCE_TEAM, array($owner->getUserid()));
        // owner-only visibility: the transfer must not rely on any permission other than ownership
        $entity = $this->getFreshEntityWithGivenUser($type, $owner, BasePermissions::UserOnly);
        $uploadId = $entity->Uploads->createFromString(FileFromString::Json, 'owned.json', '{}');

        $entity->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => self::SOURCE_TEAM));

        $this->assertOwnedBy($type, $entity->id, $target, self::SOURCE_TEAM);
        self::assertSame($target->getUserid(), $this->uploadOwner($uploadId));
    }

    #[DataProvider('entityTypes')]
    public function testSourceTeamAdminCanTransferWithinTeam(EntityType $type): void
    {
        $owner = $this->getRegularUserInTeam(self::SOURCE_TEAM);
        $target = $this->getRegularUserInTeam(self::SOURCE_TEAM, array($owner->getUserid()));
        $admin = $this->getFreshAdminInTeam(self::SOURCE_TEAM, array($owner->getUserid(), $target->getUserid()));
        $entity = $this->getFreshEntityWithGivenUser($type, $owner, BasePermissions::Team);

        $type->toInstance($admin, $entity->id)->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => self::SOURCE_TEAM));

        $this->assertOwnedBy($type, $entity->id, $target, self::SOURCE_TEAM);
    }

    #[DataProvider('entityTypes')]
    public function testSourceTeamAdminCanTransferAcrossTeams(EntityType $type): void
    {
        $owner = $this->getRegularUserInTeam(self::SOURCE_TEAM);
        $admin = $this->getFreshAdminInTeam(self::SOURCE_TEAM, array($owner->getUserid()));
        $target = $this->getRegularUserInTeam(self::OTHER_TEAM, array($owner->getUserid(), $admin->getUserid()));
        $entity = $this->getFreshEntityWithGivenUser($type, $owner, BasePermissions::Team);

        $type->toInstance($admin, $entity->id)->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => self::OTHER_TEAM));

        $this->assertOwnedBy($type, $entity->id, $target, self::OTHER_TEAM);
    }

    #[DataProvider('entityTypes')]
    public function testSysadminCanTransferWithinTeam(EntityType $type): void
    {
        $owner = $this->getRegularUserInTeam(self::SOURCE_TEAM);
        $target = $this->getRegularUserInTeam(self::SOURCE_TEAM, array($owner->getUserid()));
        $sysadmin = $this->getFreshSysadminInTeam(self::SOURCE_TEAM, array($owner->getUserid(), $target->getUserid()));
        // only the sysadmin flag can authorize this transfer
        self::assertFalse($sysadmin->isAdmin);
        $entity = $this->getFreshEntityWithGivenUser($type, $owner, BasePermissions::Team);

        $type->toInstance($sysadmin, $entity->id)->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => self::SOURCE_TEAM));

        $this->assertOwnedBy($type, $entity->id, $target, self::SOURCE_TEAM);
    }

    #[DataProvider('entityTypes')]
    public function testWriteAccessDoesNotGrantTransfer(EntityType $type): void
    {
        $owner = $this->getRegularUserInTeam(self::SOURCE_TEAM);
        $entity = $this->getFreshEntityWithGivenUser($type, $owner, BasePermissions::Team);
        $writer = $type->toInstance($this->getRegularUserInTeam(self::SOURCE_TEAM, array($owner->getUserid())), $entity->id);
        // make sure the request is refused for the right reason
        $writer->canOrExplode(AccessType::Write);

        $this->expectException(ForbiddenException::class);
        $writer->patch(Action::UpdateOwner, array('userid' => $writer->Users->getUserid(), 'team' => self::SOURCE_TEAM));
    }

    #[DataProvider('entityTypes')]
    public function testOwnerCannotTransferAcrossTeams(EntityType $type): void
    {
        $owner = $this->getRegularUserInTeam(self::SOURCE_TEAM);
        $target = $this->getRegularUserInTeam(self::OTHER_TEAM, array($owner->getUserid()));
        $entity = $this->getFreshEntityWithGivenUser($type, $owner, BasePermissions::Team);

        $this->expectException(ForbiddenException::class);
        $entity->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => self::OTHER_TEAM));
    }

    #[DataProvider('ownerInOtherTeamContext')]
    public function testOwnerLoggedInAnotherTeamCannotTransfer(EntityType $type, int $isAdminThere, int $destinationTeam): void
    {
        $owner = $this->getRegularUserInTeam(self::SOURCE_TEAM);
        $target = $this->getRegularUserInTeam($destinationTeam, array($owner->getUserid()));
        $entity = $this->getFreshEntityWithGivenUser($type, $owner, BasePermissions::Team);
        $ownerElsewhere = $this->ownerLoggedInOtherTeam($owner, $isAdminThere);
        self::assertSame((bool) $isAdminThere, $ownerElsewhere->isAdmin);

        $this->expectException(ForbiddenException::class);
        $type->toInstance($ownerElsewhere, $entity->id)->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => $destinationTeam));
    }

    #[DataProvider('entityTypes')]
    public function testOwnerCannotTransferEntryLockedBySomeoneElse(EntityType $type): void
    {
        $owner = $this->getRegularUserInTeam(self::SOURCE_TEAM);
        $target = $this->getRegularUserInTeam(self::SOURCE_TEAM, array($owner->getUserid()));
        $locker = $this->getFreshAdminInTeam(self::SOURCE_TEAM, array($owner->getUserid(), $target->getUserid()));
        $entity = $this->getFreshEntityWithGivenUser($type, $owner, BasePermissions::Team);
        $req = $this->Db->prepare(sprintf('UPDATE %s SET locked = 1, lockedby = :lockedby WHERE id = :id', $type->value));
        $req->bindValue(':lockedby', $locker->getUserid(), PDO::PARAM_INT);
        $req->bindValue(':id', $entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $this->expectException(ForbiddenException::class);
        $type->toInstance($owner, $entity->id)->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => self::SOURCE_TEAM));
    }

    #[DataProvider('entityTypes')]
    public function testTargetMustBelongToDestinationTeam(EntityType $type): void
    {
        $owner = $this->getRegularUserInTeam(self::SOURCE_TEAM);
        $outsider = $this->getRegularUserInTeam(self::OTHER_TEAM, array($owner->getUserid()));
        self::assertNotContains(self::SOURCE_TEAM, $outsider->getActiveTeamIds());
        $entity = $this->getFreshEntityWithGivenUser($type, $owner, BasePermissions::Team);

        $this->expectException(UnprocessableContentException::class);
        $entity->patch(Action::UpdateOwner, array('userid' => $outsider->getUserid(), 'team' => self::SOURCE_TEAM));
    }

    #[DataProvider('entityTypes')]
    public function testTargetMustNotBeArchivedInDestinationTeam(EntityType $type): void
    {
        $owner = $this->getRegularUserInTeam(self::SOURCE_TEAM);
        $archivedId = $this->getRegularUserInTeam(self::SOURCE_TEAM, array($owner->getUserid()))->getUserid();
        $this->setTeamMembership($archivedId, self::SOURCE_TEAM, isArchived: 1);
        $entity = $this->getFreshEntityWithGivenUser($type, $owner, BasePermissions::Team);

        $this->expectException(UnprocessableContentException::class);
        $entity->patch(Action::UpdateOwner, array('userid' => $archivedId, 'team' => self::SOURCE_TEAM));
    }

    #[DataProvider('entityTypes')]
    public function testOwnershipCannotChangeThroughRegularUpdate(EntityType $type): void
    {
        $owner = $this->getRegularUserInTeam(self::SOURCE_TEAM);
        $entity = $this->getFreshEntityWithGivenUser($type, $owner, BasePermissions::Team);

        $this->expectException(ImproperActionException::class);
        $entity->patch(Action::Update, array('userid' => $owner->getUserid(), 'team' => self::SOURCE_TEAM));
    }

    // the same person, now logged in OTHER_TEAM
    private function ownerLoggedInOtherTeam(Users $owner, int $isAdminThere): AuthenticatedUser
    {
        $this->setTeamMembership($owner->getUserid(), self::OTHER_TEAM, isAdmin: $isAdminThere);
        return new AuthenticatedUser($owner->getUserid(), self::OTHER_TEAM);
    }

    // read back as the new owner: the previous owner or the requester may have lost read access
    private function assertOwnedBy(EntityType $type, int $id, Users $expectedOwner, int $expectedTeam): void
    {
        $entityData = $type->toInstance(new AuthenticatedUser($expectedOwner->getUserid(), $expectedTeam), $id)->readOne();
        self::assertSame($expectedOwner->getUserid(), (int) $entityData['userid']);
        self::assertSame($expectedTeam, (int) $entityData['team']);
    }

    private function uploadOwner(int $uploadId): int
    {
        $req = $this->Db->prepare('SELECT userid FROM uploads WHERE id = :id');
        $req->bindValue(':id', $uploadId, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }
}
