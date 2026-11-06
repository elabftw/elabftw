<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Codeception\Attribute\DataProvider;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Scope;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Users2Teams;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Models\Users\Users;
use Elabftw\Params\DisplayParams;
use Elabftw\Traits\TestsUtilsTrait;
use RuntimeException;
use Symfony\Component\HttpFoundation\InputBag;

use function array_column;
use function array_flip;
use function array_merge;
use function json_decode;
use function json_encode;
use function sprintf;

/**
 * A user restricted in a team only sees, while acting in that team, what they own or what is explicitly shared with them (user or teamgroup)
 */
class RestrictedUserTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private const int OWNER = 2;

    private const int VIEWER = 3;

    private const int TEAM = 1;

    private const int OTHER_TEAM = 2;

    public static function entityTypes(): array
    {
        return array(
            'experiments' => array(EntityType::Experiments),
            'items' => array(EntityType::Items),
            'templates' => array(EntityType::Templates),
            'items types' => array(EntityType::ItemsTypes),
        );
    }

    #[DataProvider('entityTypes')]
    public function testRestrictedUserOnlySeesOwnedOrExplicitlyShared(EntityType $entityType): void
    {
        $sysadmin = new Users(1, self::TEAM);
        $teamgroupId = new TeamGroups($sysadmin)->create('Restricted user test group');
        new TeamGroups($sysadmin, $teamgroupId)->updateMember(array('how' => Action::Add->value, 'userid' => self::VIEWER));

        $Entity = $entityType->toInstance(new AuthenticatedUser(self::OWNER, self::TEAM));
        $create = static fn(BasePermissions $base, array $can = array()): int => $Entity->create(
            canreadBase: $base,
            canread: self::getCanJson($can),
        );
        $ViewerEntity = $entityType->toInstance(new AuthenticatedUser(self::VIEWER, self::TEAM));
        // entity => (visible when restricted, visible when not restricted)
        $expectations = array(
            'public' => array($create(BasePermissions::Full), false, true),
            'organization' => array($create(BasePermissions::Organization), false, true),
            'team' => array($create(BasePermissions::Team), false, true),
            'owner and admins' => array($create(BasePermissions::User), false, false),
            'owner only' => array($create(BasePermissions::UserOnly), false, false),
            'shared with the team' => array($create(BasePermissions::UserOnly, array('teams' => array(self::TEAM))), false, true),
            'shared with the user' => array($create(BasePermissions::UserOnly, array('users' => array(self::VIEWER))), true, true),
            'shared with a teamgroup of the user' => array($create(BasePermissions::UserOnly, array('teamgroups' => array($teamgroupId))), true, true),
            'owned by the user' => array($ViewerEntity->create(canreadBase: BasePermissions::UserOnly), true, true),
        );

        try {
            foreach (array(true, false) as $isRestricted) {
                $this->setRestricted(self::VIEWER, self::TEAM, $isRestricted);
                $viewer = new AuthenticatedUser(self::VIEWER, self::TEAM);
                $this->assertSame($isRestricted, $viewer->isRestricted());
                $listed = $this->getListedIds($entityType, $viewer);
                foreach ($expectations as $caseName => [$id, $visibleWhenRestricted, $visibleOtherwise]) {
                    $expected = $isRestricted ? $visibleWhenRestricted : $visibleOtherwise;
                    $who = $isRestricted ? 'restricted' : 'regular';
                    $this->assertSame($expected, $this->canRead($entityType, $viewer, $id), sprintf('%s: %s user reading entity "%s"', $entityType->value, $who, $caseName));
                    $this->assertSame($expected, isset($listed[$id]), sprintf('%s: entity "%s" listed for %s user', $entityType->value, $caseName, $who));
                }
            }
        } finally {
            $this->setRestricted(self::VIEWER, self::TEAM, false);
            foreach (array_column($expectations, 0) as $id) {
                $entityType->toInstance($sysadmin, $id, bypassWritePermission: true)->destroy();
            }
            new TeamGroups($sysadmin, $teamgroupId)->destroy();
        }
    }

    /**
     * Being restricted in a team has no effect while acting in another team, except that sharing with the restricted team no longer counts
     */
    public function testRestrictionIsPerTeam(): void
    {
        $sysadmin = new Users(1, self::TEAM);
        $Users2Teams = new Users2Teams($sysadmin);
        $wasAdded = $Users2Teams->create(self::VIEWER, self::OTHER_TEAM);
        $Entity = new Experiments(new AuthenticatedUser(self::OWNER, self::TEAM));
        $sharedWithTeam = $Entity->create(canreadBase: BasePermissions::UserOnly, canread: self::getCanJson(array('teams' => array(self::TEAM))));
        $organization = $Entity->create(canreadBase: BasePermissions::Organization);
        try {
            $this->setRestricted(self::VIEWER, self::TEAM, true);
            $this->assertTrue(new AuthenticatedUser(self::VIEWER, self::TEAM)->isRestricted());
            $viewerInOtherTeam = new AuthenticatedUser(self::VIEWER, self::OTHER_TEAM);
            $this->assertFalse($viewerInOtherTeam->isRestricted());
            $this->assertTrue($this->canRead(EntityType::Experiments, $viewerInOtherTeam, $organization));
            $this->assertFalse($this->canRead(EntityType::Experiments, $viewerInOtherTeam, $sharedWithTeam));
            $listed = $this->getListedIds(EntityType::Experiments, $viewerInOtherTeam);
            $this->assertArrayHasKey($organization, $listed);
            $this->assertArrayNotHasKey($sharedWithTeam, $listed);

            $this->setRestricted(self::VIEWER, self::TEAM, false);
            $viewerInOtherTeam = new AuthenticatedUser(self::VIEWER, self::OTHER_TEAM);
            $this->assertTrue($this->canRead(EntityType::Experiments, $viewerInOtherTeam, $sharedWithTeam));
            $this->assertArrayHasKey($sharedWithTeam, $this->getListedIds(EntityType::Experiments, $viewerInOtherTeam));
        } finally {
            $this->setRestricted(self::VIEWER, self::TEAM, false);
            foreach (array($sharedWithTeam, $organization) as $id) {
                new Experiments($sysadmin, $id, bypassWritePermission: true)->destroy();
            }
            if ($wasAdded) {
                $Users2Teams->destroy(self::VIEWER, self::OTHER_TEAM);
            }
        }
    }

    public function testUserCannotChangeOwnRestriction(): void
    {
        $user = new Users(self::VIEWER, self::TEAM);
        $this->assertFalse($user->isAdmin);
        $this->expectException(ForbiddenException::class);
        $user->patch(Action::PatchUser2Team, self::getPatchParams(true));
    }

    public function testAdminCanToggleRestriction(): void
    {
        $admin = $this->getUserInTeam(team: self::TEAM, admin: 1);
        try {
            $target = new Users(self::VIEWER, self::TEAM, $admin);
            $this->assertSame(1, self::getTeamRestriction($target->patch(Action::PatchUser2Team, self::getPatchParams(true))));
            $target = new Users(self::VIEWER, self::TEAM, $admin);
            $this->assertSame(0, self::getTeamRestriction($target->patch(Action::PatchUser2Team, self::getPatchParams(false))));
        } finally {
            $this->setRestricted(self::VIEWER, self::TEAM, false);
        }
    }

    public function testAdminOfAnotherTeamCannotToggleRestriction(): void
    {
        $admin = $this->getUserInTeam(team: self::OTHER_TEAM, admin: 1);
        $this->assertFalse($admin->isSysadmin());
        $this->expectException(ForbiddenException::class);
        new Users(self::VIEWER, self::TEAM, $admin)->patch(Action::PatchUser2Team, self::getPatchParams(true));
    }

    private static function getPatchParams(bool $isRestricted): array
    {
        return array('team' => self::TEAM, 'target' => 'is_restricted', 'content' => $isRestricted ? 1 : 0);
    }

    // the value of is_restricted in TEAM from the teams of a user as returned by the API
    private static function getTeamRestriction(array $user): int
    {
        foreach ($user['teams'] as $team) {
            if ($team['id'] === self::TEAM) {
                return $team['is_restricted'];
            }
        }
        throw new RuntimeException('User is not in team');
    }

    private static function getCanJson(array $can): string
    {
        $base = json_decode(AbstractEntity::EMPTY_CAN_JSON, true, 3, JSON_THROW_ON_ERROR);
        return json_encode(array_merge($base, $can), JSON_THROW_ON_ERROR);
    }

    private function setRestricted(int $userid, int $team, bool $isRestricted): void
    {
        new Users2Teams(new Users(1, self::TEAM))->patchUser2Team(
            array('team' => $team, 'target' => 'is_restricted', 'content' => BinaryValue::from((int) $isRestricted)->value),
            $userid,
        );
    }

    /**
     * @return array<int, int> ids of the entities listed for the viewer, as keys
     */
    private function getListedIds(EntityType $entityType, Users $viewer): array
    {
        $entities = $entityType->toInstance($viewer)->readAll(new DisplayParams(
            $viewer,
            $entityType,
            new InputBag(array('limit' => 9999, 'scope' => Scope::Everything->value)),
        ));
        return array_flip(array_column($entities, 'id'));
    }

    private function canRead(EntityType $entityType, Users $viewer, int $id): bool
    {
        try {
            $entityType->toInstance($viewer, $id)->canOrExplode(AccessType::Read);
            return true;
        } catch (ForbiddenException) {
            return false;
        }
    }
}
