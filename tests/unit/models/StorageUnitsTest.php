<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Links\Containers2ItemsLinks;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\InputBag;

use function array_column;
use function array_filter;
use function sprintf;

class StorageUnitsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private const string BLOCK_MOVE_CONSTRAINT = 'block_move_history';

    private StorageUnits $StorageUnits;

    protected function setUp(): void
    {
        $this->StorageUnits = new StorageUnits(new Users(1, 1), true);
    }

    public function testCreate(): void
    {
        $parentId = $this->StorageUnits->create('Test room');
        $this->assertIsInt($parentId);
        $childId = $this->StorageUnits->create('Test cupboard', $parentId);
        $this->assertIsInt($childId);
        $withPost = $this->StorageUnits->postAction(Action::Create, array('name' => 'Cupboard 2', 'parent_id' => $parentId));
        $this->assertIsInt($withPost);
        // now patch it
        $value = 'New name';
        $this->StorageUnits->setId($withPost);
        $result = $this->StorageUnits->patch(Action::Update, array('name' => $value));
        $this->assertIsArray($result);
        $this->assertEquals($value, $result['name']);
        // try create incorrectly
        $this->expectException(ImproperActionException::class);
        $this->StorageUnits->postAction(Action::Create, array());
    }

    public function testReadOne(): void
    {
        $parentId = $this->StorageUnits->create('Test room');
        $this->StorageUnits->setId($parentId);
        $this->assertIsArray($this->StorageUnits->readOne());
        // directly test destroy function too
        $this->assertTrue($this->StorageUnits->destroy());

        // Verify correct id and parent_id are returned for a child unit
        $parentId = $this->StorageUnits->create('Test freezer');
        $childId = $this->StorageUnits->create('Test box', $parentId);
        $this->StorageUnits->setId($childId);
        $result = $this->StorageUnits->readOne();
        $this->assertEquals($childId, $result['id']);
        $this->assertEquals($parentId, $result['parent_id']);
        $this->assertStringContainsString('Test freezer', $result['full_path']);
        $this->assertStringContainsString('Test box', $result['full_path']);
    }

    public function testReadAll(): void
    {
        $this->assertIsArray($this->StorageUnits->readAll());
        $this->assertIsArray($this->StorageUnits->readAllRecursive());
        $this->assertIsArray($this->StorageUnits->readAllFromStorage(1));
        $this->assertIsArray($this->StorageUnits->readCount());

        // Test hierarchy mode returns storage units, not container assignments
        $parentId = $this->StorageUnits->create('Hierarchy test freezer');
        $childId = $this->StorageUnits->create('Hierarchy test box', $parentId);

        $queryParams = $this->StorageUnits->getQueryParams(new InputBag(array('hierarchy' => 'true')));
        $result = $this->StorageUnits->readAll($queryParams);

        $this->assertIsArray($result);
        $ids = array_column($result, 'id');
        $this->assertContains($parentId, $ids);
        $this->assertContains($childId, $ids);
        $this->assertArrayHasKey('parent_id', $result[0]);
        $this->assertArrayHasKey('children_count', $result[0]);
        $this->assertArrayHasKey('occupancy', $result[0]);
        $this->assertArrayNotHasKey('entity_id', $result[0]);
    }

    public function testAnonymousUserOnlyReadsPublicEntitiesFromStorage(): void
    {
        $storageId = $this->StorageUnits->create('Anonymous read permission test');

        $teamItem = $this->getFreshItem();
        $teamItem->patch(Action::Update, array('canread_base' => BasePermissions::Team->value));
        new Containers2ItemsLinks($teamItem, $storageId)->createWithQuantity(1.0, 'mL');

        $publicItem = $this->getFreshItem();
        $publicItem->patch(Action::Update, array('canread_base' => BasePermissions::Full->value));
        new Containers2ItemsLinks($publicItem, $storageId)->createWithQuantity(1.0, 'mL');

        $StorageUnitsAsAnonymous = new StorageUnits(new AnonymousUser(1), false);
        $visibleItemIds = array_column(
            array_filter(
                $StorageUnitsAsAnonymous->readAll(),
                static fn(array $row): bool => $row['page'] === 'database',
            ),
            'entity_id',
        );

        $this->assertNotContains($teamItem->id, $visibleItemIds);
        $this->assertContains($publicItem->id, $visibleItemIds);
    }

    public function testReadAllFromStorage(): void
    {
        // create 3 containers with the same qty/unit/storage
        $Item = $this->getFreshItem();
        $storageId = $this->StorageUnits->create('A place with multiple similar containers');
        $Container2Items = new Containers2ItemsLinks($Item, $storageId);
        $Container2Items->createWithQuantity(100.0, 'mL');
        $Container2Items->createWithQuantity(100.0, 'mL');
        $Container2Items->createWithQuantity(100.0, 'mL');
        // now list them and verify we can see them all
        $res = $this->StorageUnits->readAllFromStorage($storageId);
        $this->assertCount(3, $res);
        $this->assertNotEmpty($res[0]['container2item_id']);
        // now delete the resource and verify nothing shows up in results
        $Item->destroy();
        $res = $this->StorageUnits->readAllFromStorage($storageId);
        $this->assertCount(0, $res);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/storage_units/', $this->StorageUnits->getApiPath());
    }

    public function testCreateImmutable(): void
    {
        $locations = array('Parent 1', 'Middle 1', '', 'Leaf 1');
        $resultsNumber = $this->StorageUnits->createImmutable($locations);
        // a second time to ensure we get the same number
        $this->assertEquals($resultsNumber, $this->StorageUnits->createImmutable($locations));
    }

    public function testMoveToAnotherParent(): void
    {
        $shelfA = $this->StorageUnits->create('Shelf A');
        $shelfB = $this->StorageUnits->create('Shelf B');
        $boxId = $this->StorageUnits->create('Box', $shelfA);

        $this->StorageUnits->setId($boxId);
        $result = $this->StorageUnits->patch(Action::Update, array('parent_id' => $shelfB));
        $this->assertEquals($shelfB, $result['parent_id']);
        $this->assertStringContainsString('Shelf B', $result['full_path']);
    }

    public function testMoveToRoot(): void
    {
        $shelf = $this->StorageUnits->create('Shelf C');
        $boxId = $this->StorageUnits->create('Box C', $shelf);

        $this->StorageUnits->setId($boxId);
        $result = $this->StorageUnits->patch(Action::Update, array('parent_id' => null));
        $this->assertNull($result['parent_id']);
    }

    public function testMoveToSelfIsRejected(): void
    {
        $unitId = $this->StorageUnits->create('Self-parent test');
        $this->StorageUnits->setId($unitId);
        $this->expectException(ImproperActionException::class);
        $this->StorageUnits->move($unitId);
    }

    public function testMoveToDescendantIsRejected(): void
    {
        $parentId = $this->StorageUnits->create('Cycle parent');
        $childId = $this->StorageUnits->create('Cycle child', $parentId);
        $grandchildId = $this->StorageUnits->create('Cycle grandchild', $childId);

        $this->StorageUnits->setId($parentId);
        $this->expectException(ImproperActionException::class);
        $this->StorageUnits->move($grandchildId);
    }

    public function testMoveToNonExistentParentIsRejected(): void
    {
        $unitId = $this->StorageUnits->create('No-such-parent test');
        $this->StorageUnits->setId($unitId);
        $this->expectException(ImproperActionException::class);
        $this->StorageUnits->move(PHP_INT_MAX);
    }

    public function testMoveWithoutInventoryRightsIsRejected(): void
    {
        // build a unit while we still have rights
        $unitId = $this->StorageUnits->create('Perms test');
        $newParentId = $this->StorageUnits->create('Perms test parent');

        // pick a non-admin user; instantiate with requireEditRights=true and
        // verify that patch() bails out before any DB mutation
        $user = $this->getRandomUserInTeam(2);
        $this->assertSame(0, (int) $user->userData['can_manage_inventory_locations'], 'Test fixture changed: expected an unprivileged user.');
        $StorageUnitsAsUser = new StorageUnits($user, true);
        $StorageUnitsAsUser->setId($unitId);
        $this->expectException(\Elabftw\Exceptions\IllegalActionException::class);
        $StorageUnitsAsUser->patch(Action::Update, array('parent_id' => $newParentId));
    }

    public function testAnonymousUserCannotWriteWhenEditRightsAreDisabled(): void
    {
        $StorageUnitsAsAnonymous = new StorageUnits(new AnonymousUser(1), false);

        $this->assertFalse($StorageUnitsAsAnonymous->canWrite());
        $this->expectException(\Elabftw\Exceptions\IllegalActionException::class);

        $StorageUnitsAsAnonymous->canWriteOrExplode();
    }

    public function testPatchWithNoTargetIsRejected(): void
    {
        $unitId = $this->StorageUnits->create('Patch nothing');
        $this->StorageUnits->setId($unitId);
        $this->expectException(ImproperActionException::class);
        $this->StorageUnits->patch(Action::Update, array());
    }

    public function testPatchWithNonNumericParentIdIsRejected(): void
    {
        $unitId = $this->StorageUnits->create('Bogus parent test');
        $this->StorageUnits->setId($unitId);
        $this->expectException(ImproperActionException::class);
        $this->StorageUnits->patch(Action::Update, array('parent_id' => 'bogus'));
    }

    public function testCapacityDefaultsToUnlimited(): void
    {
        $unitId = $this->StorageUnits->create('No capacity set');
        $this->StorageUnits->setId($unitId);
        $this->assertNull($this->StorageUnits->readOne()['capacity']);
    }

    public function testCreateWithCapacity(): void
    {
        $unitId = $this->StorageUnits->postAction(Action::Create, array('name' => 'Box of 96', 'capacity' => 96));
        $this->StorageUnits->setId($unitId);
        $this->assertEquals(96, $this->StorageUnits->readOne()['capacity']);
    }

    public function testPatchCapacity(): void
    {
        $unitId = $this->StorageUnits->create('Capacity patch test');
        $this->StorageUnits->setId($unitId);
        $this->assertEquals(1, $this->StorageUnits->patch(Action::Update, array('capacity' => 1))['capacity']);
        // a string works too, as sent by the frontend
        $this->assertEquals(12, $this->StorageUnits->patch(Action::Update, array('capacity' => '12'))['capacity']);
        // 0 is a real capacity: a unit that holds child units but never containers
        $this->assertEquals(0, $this->StorageUnits->patch(Action::Update, array('capacity' => 0))['capacity']);
        // only null and an empty string mean unlimited
        $this->assertNull($this->StorageUnits->patch(Action::Update, array('capacity' => null))['capacity']);
        $this->assertEquals(0, $this->StorageUnits->patch(Action::Update, array('capacity' => '0'))['capacity']);
        $this->assertNull($this->StorageUnits->patch(Action::Update, array('capacity' => ''))['capacity']);
    }

    public function testPatchWithNegativeCapacityIsRejected(): void
    {
        $unitId = $this->StorageUnits->create('Negative capacity test');
        $this->StorageUnits->setId($unitId);
        $this->expectException(ImproperActionException::class);
        $this->StorageUnits->patch(Action::Update, array('capacity' => -1));
    }

    public function testPatchWithNonNumericCapacityIsRejected(): void
    {
        $unitId = $this->StorageUnits->create('Bogus capacity test');
        $this->StorageUnits->setId($unitId);
        $this->expectException(ImproperActionException::class);
        $this->StorageUnits->patch(Action::Update, array('capacity' => 'lots'));
    }

    public function testCapacityIsCappedAtTheColumnMaximum(): void
    {
        $unitId = $this->StorageUnits->create('Huge capacity test');
        $this->StorageUnits->setId($unitId);
        // the column is INT UNSIGNED, so its ceiling is a rejection rather than a truncation
        $this->assertEquals(
            StorageUnits::MAX_CAPACITY,
            $this->StorageUnits->patch(Action::Update, array('capacity' => StorageUnits::MAX_CAPACITY))['capacity'],
        );
        $this->expectException(ImproperActionException::class);
        $this->StorageUnits->patch(Action::Update, array('capacity' => StorageUnits::MAX_CAPACITY + 1));
    }

    public function testPatchIsRolledBackWhenTheMoveFails(): void
    {
        $destinationId = $this->StorageUnits->create('Rollback destination');
        $unitId = $this->StorageUnits->create('Rollback source');
        $this->StorageUnits->setId($unitId);
        $this->StorageUnits->patch(Action::Update, array('capacity' => 5));

        // every move writes a history row, so blocking that insert fails the move at the
        // database level, after the name and capacity of the same request were written
        $this->blockMoveHistoryInserts($destinationId);
        try {
            $this->StorageUnits->patch(Action::Update, array(
                'name' => 'Name that must not survive',
                'capacity' => 42,
                'parent_id' => $destinationId,
            ));
            $this->fail('The move should have failed at the database level.');
        } catch (DatabaseErrorException) {
            // expected
        } finally {
            $this->unblockMoveHistoryInserts();
        }

        $unit = $this->StorageUnits->readOne();
        $this->assertEquals('Rollback source', $unit['name']);
        $this->assertEquals(5, $unit['capacity']);
        $this->assertNull($unit['parent_id']);
    }

    public function testCountContainersIsNotAffectedByBinning(): void
    {
        $Item = $this->getFreshItem();
        $storageId = $this->StorageUnits->create('Box for occupancy count');
        new Containers2ItemsLinks($Item, $storageId)->createWithQuantity(1.0, 'mL');
        $this->assertSame(1, $this->StorageUnits->countContainers($storageId));
        // binning the resource does not take the container out of the box
        $Item->destroy();
        $this->assertSame(1, $this->StorageUnits->countContainers($storageId));
    }

    public function testHierarchyOccupancyCountsOnlyDirectContainers(): void
    {
        $Item = $this->getFreshItem();
        $parentId = $this->StorageUnits->create('Freezer holding a box');
        $childId = $this->StorageUnits->create('Box holding a tube', $parentId);
        new Containers2ItemsLinks($Item, $childId)->createWithQuantity(1.0, 'mL');

        $occupancy = $this->readOccupancyByUnitId();
        // the container is inside the box, so the freezer around it stays empty: a capacity
        // limits what a unit holds directly, never what its descendants hold
        $this->assertEquals(0, $occupancy[$parentId]);
        $this->assertEquals(1, $occupancy[$childId]);
    }

    public function testHierarchyOccupancyMatchesTheGuard(): void
    {
        $Item = $this->getFreshItem();
        $storageId = $this->StorageUnits->create('Box the tree and the guard must agree on');
        new Containers2ItemsLinks($Item, $storageId)->createWithQuantity(1.0, 'mL');
        $Item->destroy();
        // a displayed free slot the guard would then refuse is worse than showing nothing,
        // so the tree has to count exactly what assertHasRoom() counts
        $occupancy = $this->readOccupancyByUnitId();
        $this->assertEquals($this->StorageUnits->countContainers($storageId), $occupancy[$storageId]);
        $this->assertEquals(1, $occupancy[$storageId]);
    }

    public function testReadOneOccupancyIsThatOfTheRequestedUnit(): void
    {
        $Item = $this->getFreshItem();
        $parentId = $this->StorageUnits->create('Parent walked through on the way up');
        $childId = $this->StorageUnits->create('Child that holds the container', $parentId);
        new Containers2ItemsLinks($Item, $childId)->createWithQuantity(1.0, 'mL');

        // the cte climbs to the root, so this pins the count to the unit that was asked for
        $this->StorageUnits->setId($childId);
        $this->assertEquals(1, $this->StorageUnits->readOne()['occupancy']);
        $this->StorageUnits->setId($parentId);
        $this->assertEquals(0, $this->StorageUnits->readOne()['occupancy']);
    }

    public function testAssertHasRoomWithoutCapacityNeverThrows(): void
    {
        $Item = $this->getFreshItem();
        $storageId = $this->StorageUnits->create('Box with no capacity');
        $Links = new Containers2ItemsLinks($Item, $storageId);
        $Links->createWithQuantity(1.0, 'mL');
        $Links->createWithQuantity(1.0, 'mL');
        $this->StorageUnits->assertHasRoom($storageId);
        $this->assertSame(2, $this->StorageUnits->countContainers($storageId));
    }

    public function testAssertHasRoomThrowsWhenFull(): void
    {
        $Item = $this->getFreshItem();
        $storageId = $this->StorageUnits->create('Box that is full');
        new Containers2ItemsLinks($Item, $storageId)->createWithQuantity(1.0, 'mL');
        $this->StorageUnits->setId($storageId);
        $this->StorageUnits->patch(Action::Update, array('capacity' => 1));
        $this->expectException(ImproperActionException::class);
        $this->StorageUnits->assertHasRoom($storageId);
    }

    public function testAssertHasRoomRejectsAnEmptyUnitWithZeroCapacity(): void
    {
        $storageId = $this->StorageUnits->create('Room that holds freezers, not tubes');
        $this->StorageUnits->setId($storageId);
        $this->StorageUnits->patch(Action::Update, array('capacity' => 0));
        try {
            $this->StorageUnits->assertHasRoom($storageId);
            $this->fail('Expected ImproperActionException was not thrown.');
        } catch (ImproperActionException $e) {
            // an empty structural node must not report itself as merely full
            $this->assertStringContainsString('capacity is zero', $e->getMessage());
        }
    }

    public function testZeroCapacityStillAcceptsChildUnits(): void
    {
        $parentId = $this->StorageUnits->create('Building with no storage of its own');
        $this->StorageUnits->setId($parentId);
        $this->StorageUnits->patch(Action::Update, array('capacity' => 0));
        // capacity constrains containers, never child units
        $childId = $this->StorageUnits->create('Freezer inside it', $parentId);
        $this->StorageUnits->setId($childId);
        $this->assertEquals($parentId, $this->StorageUnits->readOne()['parent_id']);
    }

    public function testAssertHasRoomOnMissingUnitIsTolerated(): void
    {
        // no row to read a capacity from: the foreign key deals with it, not the guard
        $this->StorageUnits->assertHasRoom(PHP_INT_MAX);
        $this->assertSame(0, $this->StorageUnits->countContainers(PHP_INT_MAX));
    }

    public function testPatchDoesNotPersistRenameWhenMoveFails(): void
    {
        $original = 'Atomicity test original';
        $unitId = $this->StorageUnits->create($original);
        $this->StorageUnits->setId($unitId);

        try {
            $this->StorageUnits->patch(Action::Update, array(
                'name' => 'Atomicity test renamed',
                'parent_id' => PHP_INT_MAX,
            ));
            $this->fail('Expected ImproperActionException was not thrown.');
        } catch (ImproperActionException) {
            // expected
        }

        $this->assertEquals($original, $this->StorageUnits->readOne()['name']);
    }

    public function testMoveRecordsHistoryRow(): void
    {
        $shelfA = $this->StorageUnits->create('History shelf A');
        $shelfB = $this->StorageUnits->create('History shelf B');
        $boxId = $this->StorageUnits->create('History box', $shelfA);

        $this->StorageUnits->setId($boxId);
        $this->StorageUnits->move($shelfB);

        $history = $this->StorageUnits->readHistory();
        $this->assertCount(1, $history);
        $this->assertEquals($shelfA, (int) $history[0]['old_parent_id']);
        $this->assertEquals($shelfB, (int) $history[0]['new_parent_id']);
        $this->assertEquals(1, (int) $history[0]['users_id']);
    }

    public function testMoveToRootRecordsNullNewParent(): void
    {
        $shelf = $this->StorageUnits->create('History shelf for root move');
        $boxId = $this->StorageUnits->create('History box for root move', $shelf);

        $this->StorageUnits->setId($boxId);
        $this->StorageUnits->move(null);

        $history = $this->StorageUnits->readHistory();
        $this->assertCount(1, $history);
        $this->assertEquals($shelf, (int) $history[0]['old_parent_id']);
        $this->assertNull($history[0]['new_parent_id']);
    }

    public function testNoOpMoveDoesNotRecordHistory(): void
    {
        $shelf = $this->StorageUnits->create('History no-op shelf');
        $boxId = $this->StorageUnits->create('History no-op box', $shelf);

        $this->StorageUnits->setId($boxId);
        $this->StorageUnits->move($shelf);

        $this->assertCount(0, $this->StorageUnits->readHistory());
    }

    public function testPatchWithNameAndParentRecordsOneMove(): void
    {
        $shelfA = $this->StorageUnits->create('History combined shelf A');
        $shelfB = $this->StorageUnits->create('History combined shelf B');
        $boxId = $this->StorageUnits->create('History combined box', $shelfA);

        $this->StorageUnits->setId($boxId);
        $this->StorageUnits->patch(Action::Update, array(
            'name' => 'History combined box renamed',
            'parent_id' => $shelfB,
        ));

        $history = $this->StorageUnits->readHistory();
        $this->assertCount(1, $history);
        $this->assertEquals($shelfA, (int) $history[0]['old_parent_id']);
        $this->assertEquals($shelfB, (int) $history[0]['new_parent_id']);
    }

    public function testMoveOnDeletedUnitThrowsResourceNotFound(): void
    {
        $unitId = $this->StorageUnits->create('Will be deleted');
        $this->StorageUnits->setId($unitId);
        $this->StorageUnits->destroy();
        $this->expectException(\Elabftw\Exceptions\ResourceNotFoundException::class);
        $this->StorageUnits->move(null);
    }

    public function testReadHistoryRequiresWriteRights(): void
    {
        $unitId = $this->StorageUnits->create('History auth test');
        $user = $this->getRandomUserInTeam(2);
        $this->assertSame(0, (int) $user->userData['can_manage_inventory_locations'], 'Test fixture changed: expected an unprivileged user.');
        $StorageUnitsAsUser = new StorageUnits($user, true);
        $StorageUnitsAsUser->setId($unitId);
        $this->expectException(\Elabftw\Exceptions\IllegalActionException::class);
        $StorageUnitsAsUser->readHistory();
    }

    public function testHistorySurvivesUnitDeletion(): void
    {
        $shelfA = $this->StorageUnits->create('Survival shelf A');
        $shelfB = $this->StorageUnits->create('Survival shelf B');
        $boxId = $this->StorageUnits->create('Survival box', $shelfA);

        $this->StorageUnits->setId($boxId);
        $this->StorageUnits->move($shelfB);
        $this->assertCount(1, $this->StorageUnits->readHistory());

        $this->StorageUnits->destroy();

        // re-set the (now-orphaned) id and confirm the audit row is still there
        $this->StorageUnits->setId($boxId);
        $history = $this->StorageUnits->readHistory();
        $this->assertCount(1, $history);
        $this->assertEquals($shelfA, (int) $history[0]['old_parent_id']);
        $this->assertEquals($shelfB, (int) $history[0]['new_parent_id']);
    }

    /**
     * Reject history rows pointing at $destinationId. A CHECK constraint rather than a
     * trigger, as triggers need SUPER when binary logging is on. The destination is
     * freshly created, so no existing row can violate the constraint as it is added.
     */
    private function blockMoveHistoryInserts(int $destinationId): void
    {
        $Db = Db::getConnection();
        $Db->execute($Db->prepare(sprintf(
            'ALTER TABLE storage_units_history
                ADD CONSTRAINT %s CHECK (new_parent_id <> %d)',
            self::BLOCK_MOVE_CONSTRAINT,
            $destinationId,
        )));
    }

    private function unblockMoveHistoryInserts(): void
    {
        $Db = Db::getConnection();
        // MySQL has no DROP CHECK IF EXISTS
        $Db->execute($Db->prepare(
            'ALTER TABLE storage_units_history DROP CHECK ' . self::BLOCK_MOVE_CONSTRAINT
        ));
    }

    /**
     * Occupancy of every unit, keyed by unit id, as the storage tree receives it.
     */
    private function readOccupancyByUnitId(): array
    {
        $queryParams = $this->StorageUnits->getQueryParams(new InputBag(array('hierarchy' => 'true')));
        return array_column($this->StorageUnits->readAll($queryParams), 'occupancy', 'id');
    }
}
