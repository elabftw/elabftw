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

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Links\Containers2ItemsLinks;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\InputBag;

use function array_column;

class StorageUnitsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

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
        $this->assertArrayNotHasKey('entity_id', $result[0]);
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
}
