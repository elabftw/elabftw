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
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Scope;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Models\Users\Users;
use Elabftw\Params\DisplayParams;
use Elabftw\Services\TeamsHelper;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\InputBag;

use function array_column;
use function array_merge;
use function array_unique;
use function count;
use function json_decode;
use function json_encode;
use function sprintf;

class EntitySqlBuilderTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    // the different ways readShow() can build its query
    private const array LISTING_QUERIES = array(
        // default page load: uses the fast path
        'default page' => array('order' => 'lastchange', 'sort' => 'desc', 'limit' => 9999),
        'regular' => array('order' => 'id'),
        'full' => array('full' => 1),
    );

    /**
     * When listing entities, the write access of the user on each of them is present in the data,
     * and it matches the one from the Permissions class used when acting on a single entity
     */
    #[DataProvider('entityTypes')]
    public function testWriteAccessInListing(EntityType $entityType): void
    {
        $owner = new AuthenticatedUser(2, 1);
        $viewers = array(
            'owner' => $owner,
            'team member' => new AuthenticatedUser(3, 1),
            'team admin' => $this->getUserInTeam(team: 1, admin: 1),
            'user of another team' => $this->getUserInTeam(team: 2),
            'admin of another team' => $this->getUserInTeam(team: 2, admin: 1),
        );
        $this->assertFalse($owner->isAdmin);
        $this->assertFalse($viewers['team member']->isAdmin);
        $this->assertTrue($viewers['team admin']->isAdmin);
        $this->assertFalse(new TeamsHelper(1)->isAdminInTeam($viewers['admin of another team']->getUserid()));
        $this->assertCount(count($viewers), array_unique(array_column($viewers, 'userid')));

        $canwriteUsers = json_decode(AbstractEntity::EMPTY_CAN_JSON, true, 3, JSON_THROW_ON_ERROR);
        $canwriteUsers['users'] = array($viewers['team member']->getUserid());
        $canwriteTeams = json_decode(AbstractEntity::EMPTY_CAN_JSON, true, 3, JSON_THROW_ON_ERROR);
        $canwriteTeams['teams'] = array(2);

        // everyone can read these entities, so they are listed for all viewers
        $Entity = $entityType->toInstance($owner);
        $create = static fn(BasePermissions $canwriteBase, array $canwrite = array()): int => $Entity->create(
            canreadBase: BasePermissions::Organization,
            canwriteBase: $canwriteBase,
            canwrite: $canwrite === array() ? AbstractEntity::EMPTY_CAN_JSON : json_encode($canwrite, JSON_THROW_ON_ERROR),
        );
        $lockedId = $create(BasePermissions::Team);
        $Entity->setId($lockedId);
        $Entity->lock();
        // expected write access, in the same order as $viewers. null: depends on the populated data, only compared with Permissions
        $expectations = array(
            'owner and admins' => array($create(BasePermissions::User), array(1, 0, 1, 0, 0)),
            'team' => array($create(BasePermissions::Team), array(1, 1, 1, 0, 0)),
            'owner only' => array($create(BasePermissions::UserOnly), array(1, 0, 0, 0, 0)),
            'organization' => array($create(BasePermissions::Organization), array(1, 1, 1, 1, 1)),
            'shared with a user' => array($create(BasePermissions::UserOnly, $canwriteUsers), array(1, 1, 0, 0, 0)),
            'shared with a team' => array($create(BasePermissions::UserOnly, $canwriteTeams), array(1, null, null, 1, 1)),
            // only the locker and the Admins of the entity team can write to a locked entity
            'locked' => array($lockedId, array(1, 0, 1, 0, 0)),
        );

        try {
            $index = 0;
            foreach ($viewers as $viewerName => $viewer) {
                // what the Permissions class says when acting on the entity
                $canWrite = array();
                foreach ($expectations as $caseName => [$id, $expected]) {
                    $canWrite[$id] = $this->canWrite($entityType, $viewer, $id) ? 1 : 0;
                    if ($expected[$index] !== null) {
                        $this->assertSame($expected[$index], $canWrite[$id], sprintf('%s: permissions of %s on entity "%s"', $entityType->value, $viewerName, $caseName));
                    }
                }
                // what the listing says
                foreach (self::LISTING_QUERIES as $queryName => $query) {
                    $writeAccess = $this->getWriteAccessFromListing($entityType, $viewer, $owner, $query);
                    foreach ($expectations as $caseName => [$id]) {
                        $message = sprintf('%s: entity "%s" listed by %s with %s query', $entityType->value, $caseName, $viewerName, $queryName);
                        $this->assertArrayHasKey($id, $writeAccess, $message);
                        $this->assertSame($canWrite[$id], $writeAccess[$id], $message);
                    }
                }
                $index++;
            }
        } finally {
            $Entity->setId($lockedId);
            $Entity->unlock();
            foreach (array_column($expectations, 0) as $id) {
                $Entity->setId($id);
                $Entity->destroy();
            }
        }
    }

    public function testAnonymousUserHasNoWriteAccessInListing(): void
    {
        $owner = new AuthenticatedUser(2, 1);
        $Experiments = new Experiments($owner);
        $id = $Experiments->create(canreadBase: BasePermissions::Full, canwriteBase: BasePermissions::Team);
        $Experiments->setId($id);
        $anon = new AnonymousUser(1);
        try {
            $writeAccess = $this->getWriteAccessFromListing(EntityType::Experiments, $anon, $owner, self::LISTING_QUERIES['regular']);
            $this->assertArrayHasKey($id, $writeAccess);
            $this->assertSame(0, $writeAccess[$id]);
            $this->assertFalse($this->canWrite(EntityType::Experiments, $anon, $id));
        } finally {
            $Experiments->destroy();
        }
    }

    /**
     * @return array<int, int> write access of the viewer on the listed entities of the owner, indexed by entity id
     */
    private function getWriteAccessFromListing(EntityType $entityType, Users $viewer, Users $owner, array $query): array
    {
        // use a fresh model for each request, as readShow() collects bind values
        $entities = $entityType->toInstance($viewer)->readAll(new DisplayParams(
            $viewer,
            $entityType,
            new InputBag(array_merge($query, array(
                'owner' => $owner->getUserid(),
                'scope' => Scope::Everything->value,
            ))),
        ));
        return array_column($entities, 'has_write_access', 'id');
    }

    private function canWrite(EntityType $entityType, Users $viewer, int $id): bool
    {
        try {
            $entityType->toInstance($viewer, $id)->canOrExplode(AccessType::Write);
            return true;
        } catch (ForbiddenException) {
            return false;
        }
    }
}
