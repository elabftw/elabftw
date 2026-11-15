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

use Codeception\Attribute\DataProvider;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\FileFromString;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\Users\Users;
use PDO;
use PHPUnit\Framework\TestCase;

use function array_map;
use function implode;
use function sprintf;

/**
 * Authorization rules of Action::UpdateOwner, for every entity type sharing AbstractEntity::updateOwnership()
 *
 * Roles are set up explicitly inside a rolled-back transaction instead of relying on fixture data,
 * so every test knows exactly which flags (owner, admin, sysadmin, archived) the actors have.
 */
final class OwnershipTransferTest extends TestCase
{
    private const int SOURCE_TEAM = 1;

    private const int OTHER_TEAM = 2;

    private Db $Db;

    /** @var list<int> users already picked in the current test */
    private array $picked = array();

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->beginTransaction();
        $this->picked = array();
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
            'experiments templates' => array(EntityType::Templates),
            'resources templates' => array(EntityType::ItemsTypes),
        );
    }

    /**
     * Owner logged in another team than the entry's team, with or without admin rights there, aiming at either team
     */
    public static function ownerInOtherTeamContext(): array
    {
        $cases = array();
        foreach (self::entityTypes() as $name => [$type]) {
            $cases[$name . ', member there, within entry team'] = array($type, 0, self::SOURCE_TEAM);
            $cases[$name . ', admin there, within entry team'] = array($type, 1, self::SOURCE_TEAM);
            $cases[$name . ', admin there, into that team'] = array($type, 1, self::OTHER_TEAM);
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
        );
        $cases = array();
        foreach (self::entityTypes() as $name => [$type]) {
            foreach ($roles as $role => $expected) {
                $cases[$name . ', ' . $role] = array($type, $role, $expected);
            }
        }
        return $cases;
    }

    #[DataProvider('transferButtonCases')]
    public function testCanTransferOwnership(EntityType $type, string $role, bool $expected): void
    {
        $owner = $this->regularUser(self::SOURCE_TEAM);
        // visible across teams so every role can load the entry
        $entity = $this->createEntity($type, $owner, BasePermissions::Organization);
        $requester = match ($role) {
            'owner in entry team' => $owner,
            'owner logged in another team' => (function () use ($owner): Users {
                $this->setMembership($owner->getUserid(), self::OTHER_TEAM, isAdmin: 1);
                return new Users($owner->getUserid(), self::OTHER_TEAM);
            })(),
            'writer of entry team' => $this->regularUser(self::SOURCE_TEAM),
            'admin of entry team' => $this->adminUser(self::SOURCE_TEAM),
            'admin of another team' => $this->adminUser(self::OTHER_TEAM),
            'sysadmin' => $this->sysadminUser(self::SOURCE_TEAM),
            default => self::fail(sprintf('Unknown role: %s', $role)),
        };

        self::assertSame($expected, $type->toInstance($requester, $entity->id)->canTransferOwnership());
    }

    #[DataProvider('entityTypes')]
    public function testOwnerCanTransferWithinTeam(EntityType $type): void
    {
        $owner = $this->regularUser(self::SOURCE_TEAM);
        $target = $this->regularUser(self::SOURCE_TEAM);
        // owner-only visibility: the transfer must not rely on any permission other than ownership
        $entity = $this->createEntity($type, $owner, BasePermissions::UserOnly);
        $uploadId = $entity->Uploads->createFromString(FileFromString::Json, 'owned.json', '{}');

        $entity->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => self::SOURCE_TEAM));

        $this->assertOwnedBy($type, $entity->id, $target, self::SOURCE_TEAM);
        self::assertSame($target->getUserid(), $this->uploadOwner($uploadId));
    }

    #[DataProvider('entityTypes')]
    public function testSourceTeamAdminCanTransferWithinTeam(EntityType $type): void
    {
        $entity = $this->createEntity($type, $this->regularUser(self::SOURCE_TEAM));
        $target = $this->regularUser(self::SOURCE_TEAM);
        $admin = $this->adminUser(self::SOURCE_TEAM);

        $type->toInstance($admin, $entity->id)->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => self::SOURCE_TEAM));

        $this->assertOwnedBy($type, $entity->id, $target, self::SOURCE_TEAM);
    }

    #[DataProvider('entityTypes')]
    public function testSourceTeamAdminCanTransferAcrossTeams(EntityType $type): void
    {
        $entity = $this->createEntity($type, $this->regularUser(self::SOURCE_TEAM));
        $target = $this->regularUser(self::OTHER_TEAM);
        $admin = $this->adminUser(self::SOURCE_TEAM);

        $type->toInstance($admin, $entity->id)->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => self::OTHER_TEAM));

        $this->assertOwnedBy($type, $entity->id, $target, self::OTHER_TEAM);
    }

    #[DataProvider('entityTypes')]
    public function testSysadminCanTransferWithinTeam(EntityType $type): void
    {
        $entity = $this->createEntity($type, $this->regularUser(self::SOURCE_TEAM));
        $target = $this->regularUser(self::SOURCE_TEAM);
        $sysadmin = $this->sysadminUser(self::SOURCE_TEAM);
        self::assertFalse($sysadmin->isAdmin);

        $type->toInstance($sysadmin, $entity->id)->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => self::SOURCE_TEAM));

        $this->assertOwnedBy($type, $entity->id, $target, self::SOURCE_TEAM);
    }

    #[DataProvider('entityTypes')]
    public function testWriteAccessDoesNotGrantTransfer(EntityType $type): void
    {
        $owner = $this->regularUser(self::SOURCE_TEAM);
        $entity = $this->createEntity($type, $owner);
        $writer = $type->toInstance($this->regularUser(self::SOURCE_TEAM), $entity->id);
        // make sure the request is refused for the right reason
        $writer->canOrExplode(AccessType::Write);

        $this->expectException(ForbiddenException::class);
        $writer->patch(Action::UpdateOwner, array('userid' => $writer->Users->getUserid(), 'team' => self::SOURCE_TEAM));
    }

    #[DataProvider('entityTypes')]
    public function testOwnerCannotTransferAcrossTeams(EntityType $type): void
    {
        $owner = $this->regularUser(self::SOURCE_TEAM);
        $entity = $this->createEntity($type, $owner);
        $target = $this->regularUser(self::OTHER_TEAM);

        $this->expectException(ForbiddenException::class);
        $entity->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => self::OTHER_TEAM));
    }

    #[DataProvider('ownerInOtherTeamContext')]
    public function testOwnerLoggedInAnotherTeamCannotTransfer(EntityType $type, int $isAdminThere, int $destinationTeam): void
    {
        $owner = $this->regularUser(self::SOURCE_TEAM);
        $entity = $this->createEntity($type, $owner);
        $this->setMembership($owner->getUserid(), self::OTHER_TEAM, isAdmin: $isAdminThere);
        $ownerElsewhere = new Users($owner->getUserid(), self::OTHER_TEAM);
        self::assertSame((bool) $isAdminThere, $ownerElsewhere->isAdmin);
        $target = $this->regularUser($destinationTeam);

        $this->expectException(ForbiddenException::class);
        $type->toInstance($ownerElsewhere, $entity->id)->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => $destinationTeam));
    }

    #[DataProvider('entityTypes')]
    public function testOwnerCannotTransferEntryLockedBySomeoneElse(EntityType $type): void
    {
        $owner = $this->regularUser(self::SOURCE_TEAM);
        $entity = $this->createEntity($type, $owner);
        $locker = $this->adminUser(self::SOURCE_TEAM);
        $req = $this->Db->prepare(sprintf('UPDATE %s SET locked = 1, lockedby = :lockedby WHERE id = :id', $type->value));
        $req->bindValue(':lockedby', $locker->getUserid(), PDO::PARAM_INT);
        $req->bindValue(':id', $entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $target = $this->regularUser(self::SOURCE_TEAM);

        $this->expectException(ForbiddenException::class);
        $type->toInstance($owner, $entity->id)->patch(Action::UpdateOwner, array('userid' => $target->getUserid(), 'team' => self::SOURCE_TEAM));
    }

    #[DataProvider('entityTypes')]
    public function testTargetMustBelongToDestinationTeam(EntityType $type): void
    {
        $entity = $this->createEntity($type, $this->regularUser(self::SOURCE_TEAM));
        $outsider = $this->regularUser(self::OTHER_TEAM);
        self::assertNotContains(self::SOURCE_TEAM, $outsider->getActiveTeamIds());

        $this->expectException(UnprocessableContentException::class);
        $entity->patch(Action::UpdateOwner, array('userid' => $outsider->getUserid(), 'team' => self::SOURCE_TEAM));
    }

    #[DataProvider('entityTypes')]
    public function testTargetMustNotBeArchivedInDestinationTeam(EntityType $type): void
    {
        $entity = $this->createEntity($type, $this->regularUser(self::SOURCE_TEAM));
        $archivedId = $this->pickUserId(self::SOURCE_TEAM);
        $this->setMembership($archivedId, self::SOURCE_TEAM, isArchived: 1);

        $this->expectException(UnprocessableContentException::class);
        $entity->patch(Action::UpdateOwner, array('userid' => $archivedId, 'team' => self::SOURCE_TEAM));
    }

    #[DataProvider('entityTypes')]
    public function testOwnershipCannotChangeThroughRegularUpdate(EntityType $type): void
    {
        $owner = $this->regularUser(self::SOURCE_TEAM);
        $entity = $this->createEntity($type, $owner);

        $this->expectException(ImproperActionException::class);
        $entity->patch(Action::Update, array('userid' => $owner->getUserid(), 'team' => self::SOURCE_TEAM));
    }

    private function createEntity(EntityType $type, Users $owner, BasePermissions $base = BasePermissions::Team): AbstractEntity
    {
        $entity = $type->toInstance($owner);
        $id = $entity->create(canreadBase: $base, canwriteBase: $base);
        $entity->setId($id);
        return $entity;
    }

    // read back as the new owner: the previous owner or the requester may have lost read access
    private function assertOwnedBy(EntityType $type, int $id, Users $expectedOwner, int $expectedTeam): void
    {
        $entityData = $type->toInstance(new Users($expectedOwner->getUserid(), $expectedTeam), $id)->readOne();
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

    private function regularUser(int $team): Users
    {
        $user = new Users($this->pickUserId($team), $team);
        self::assertFalse($user->isAdmin);
        self::assertFalse($user->isSysadmin());
        return $user;
    }

    private function adminUser(int $team): Users
    {
        $userid = $this->pickUserId($team);
        $this->setMembership($userid, $team, isAdmin: 1);
        $user = new Users($userid, $team);
        self::assertTrue($user->isAdmin);
        self::assertFalse($user->isSysadmin());
        return $user;
    }

    // a sysadmin without admin rights in the team, so only the sysadmin flag can authorize the transfer
    private function sysadminUser(int $team): Users
    {
        $userid = $this->pickUserId($team);
        $req = $this->Db->prepare('UPDATE users SET is_sysadmin = 1 WHERE userid = :userid');
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        $user = new Users($userid, $team);
        self::assertTrue($user->isSysadmin());
        return $user;
    }

    /**
     * Pick an active, validated user with no special role in the team, different from those already picked in this test
     */
    private function pickUserId(int $team): int
    {
        $exclude = $this->picked === array() ? '' : sprintf(
            'AND u2t.users_id NOT IN (%s)',
            implode(',', array_map(intval(...), $this->picked)),
        );
        $sql = sprintf('SELECT u2t.users_id FROM users2teams AS u2t
            INNER JOIN users AS u ON u.userid = u2t.users_id
            WHERE u2t.teams_id = :team
                AND u2t.is_admin = 0
                AND u2t.is_archived = 0
                AND u.is_sysadmin = 0
                AND u.validated = 1
                AND (u.valid_until IS NULL OR u.valid_until > NOW())
                %s
            ORDER BY u2t.users_id LIMIT 1', $exclude);
        $req = $this->Db->prepare($sql);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $this->Db->execute($req);
        $userid = $req->fetchColumn();
        self::assertNotFalse($userid, sprintf('Not enough regular users in team %d for this test.', $team));
        $this->picked[] = (int) $userid;
        return (int) $userid;
    }

    // create or overwrite the membership so pre-existing flags (e.g. an archived membership) cannot leak into the test
    private function setMembership(int $userid, int $team, int $isAdmin = 0, int $isArchived = 0): void
    {
        $req = $this->Db->prepare('INSERT IGNORE INTO users2teams (users_id, teams_id) VALUES (:userid, :team)');
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $this->Db->execute($req);
        $req = $this->Db->prepare('UPDATE users2teams SET is_admin = :is_admin, is_archived = :is_archived WHERE users_id = :userid AND teams_id = :team');
        $req->bindValue(':is_admin', $isAdmin, PDO::PARAM_INT);
        $req->bindValue(':is_archived', $isArchived, PDO::PARAM_INT);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}
