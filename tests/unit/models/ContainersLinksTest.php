<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\ContainerDeletionReason;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Links\Containers2ItemsLinks;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;

use function sprintf;
use function str_repeat;

class ContainersLinksTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private StorageUnits $StorageUnits;

    protected function setUp(): void
    {
        $this->StorageUnits = new StorageUnits(new Users(1, 1), true);
    }

    protected function tearDown(): void
    {
        $this->setCaptureDeletionReason(0);
        $this->setCaptureDeletionReason(0, 2);
    }

    public function testMoveContainerToAnotherStorage(): void
    {
        $Item = $this->getFreshItem();
        $boxA = $this->StorageUnits->create('Box A');
        $boxB = $this->StorageUnits->create('Box B');

        $LinksAtA = new Containers2ItemsLinks($Item, $boxA);
        $LinksAtA->createWithQuantity(10.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $result = $Links->patch(Action::Update, array('storage_id' => $boxB));
        $this->assertEquals($boxB, (int) $result['storage_id']);

        $entry = $this->latestChangelogEntry($Item, 'container_moved');
        $this->assertNotNull($entry);
        $this->assertStringContainsString(sprintf('(container #%d)', $rowId), $entry['content']);

        // moving to the same destination is a no-op (no exception)
        $result = $Links->patch(Action::Update, array('storage_id' => $boxB));
        $this->assertEquals($boxB, (int) $result['storage_id']);
    }

    public function testMoveContainerToNonExistentStorageIsRejected(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for missing-target test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(1.0, 'g');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $this->expectException(\Elabftw\Exceptions\ResourceNotFoundException::class);
        $Links->patch(Action::Update, array('storage_id' => PHP_INT_MAX));
    }

    public function testMoveContainerWithInvalidStorageIdIsRejected(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for invalid-id test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(1.0, 'g');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $this->expectException(ImproperActionException::class);
        $Links->patch(Action::Update, array('storage_id' => 0));
    }

    public function testPatchQtyStillWorks(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for qty test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(1.0, 'g');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $result = $Links->patch(Action::Update, array('qty_stored' => 42.5, 'qty_unit' => 'mg'));
        $this->assertEquals(42.5, (float) $result['qty_stored']);
        $this->assertEquals('mg', $result['qty_unit']);
    }

    public function testPatchQtyZeroIsPersisted(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for qty zero test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(10.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $result = $Links->patch(Action::Update, array('qty_stored' => 0));
        $this->assertEquals(0.0, (float) $result['qty_stored']);
    }

    public function testCannotCreateContainerWithoutParentWriteAccess(): void
    {
        $Item = $this->getFreshItemWithGivenUser($this->getUserInTeam(1, 1));
        $box = $this->StorageUnits->create('Box for create authorization test');
        $ReadOnlyItem = new Items($this->getUserInTeam(1), $Item->id);

        $Links = new Containers2ItemsLinks($ReadOnlyItem, $box);
        $this->expectException(ForbiddenException::class);
        $Links->createWithQuantity(1.0, 'g');
    }

    public function testCannotPatchContainerWithoutParentWriteAccess(): void
    {
        $Item = $this->getFreshItemWithGivenUser($this->getUserInTeam(1, 1));
        $box = $this->StorageUnits->create('Box for patch authorization test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(1.0, 'g');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $ReadOnlyItem = new Items($this->getUserInTeam(1), $Item->id);
        $Links = new Containers2ItemsLinks($ReadOnlyItem, $rowId);
        $this->expectException(ForbiddenException::class);
        $Links->patch(Action::Update, array('qty_stored' => 999));
    }

    public function testCannotPatchContainerFromAnotherParentItem(): void
    {
        $ItemA = $this->getFreshItemWithGivenUser($this->getUserInTeam(1, 1));
        $ItemB = $this->getFreshItemWithGivenUser($this->getUserInTeam(1, 1));
        $box = $this->StorageUnits->create('Box for parent binding test');

        $LinksA = new Containers2ItemsLinks($ItemA, $box);
        $LinksA->createWithQuantity(1.0, 'g');
        $LinksB = new Containers2ItemsLinks($ItemB, $box);
        $LinksB->createWithQuantity(2.0, 'g');
        $rowB = $this->latestContainerRowId('containers2items', $ItemB->id);

        $Links = new Containers2ItemsLinks($ItemA, $rowB);
        try {
            $Links->patch(Action::Update, array('qty_stored' => 999));
            $this->fail('Expected ResourceNotFoundException was not thrown.');
        } catch (ResourceNotFoundException) {
            $this->addToAssertionCount(1);
        }

        $this->assertEquals(2.0, $this->readContainerQty('containers2items', $rowB));
    }

    public function testCreateLogsToChangelog(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for create changelog test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(5.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $entry = $this->latestChangelogEntry($Item, 'container_created');
        $this->assertNotNull($entry);
        $this->assertStringContainsString('Added container with 5.00 mL', $entry['content']);
        $this->assertStringContainsString(sprintf('(container #%d)', $rowId), $entry['content']);
    }

    public function testQtyChangeLogsToChangelog(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for qty changelog test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(10.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $Links->patch(Action::Update, array('qty_stored' => 3.0));

        $entry = $this->latestChangelogEntry($Item, 'container_qty_changed');
        $this->assertNotNull($entry);
        $this->assertStringContainsString('from "10.00" to "3.00"', $entry['content']);
        $this->assertStringContainsString(sprintf('(container #%d)', $rowId), $entry['content']);
    }

    public function testUnitChangeLogsToChangelog(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for unit changelog test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(10.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $Links->patch(Action::Update, array('qty_unit' => 'g'));

        $entry = $this->latestChangelogEntry($Item, 'container_unit_changed');
        $this->assertNotNull($entry);
        $this->assertStringContainsString('from "mL" to "g"', $entry['content']);
        $this->assertStringContainsString(sprintf('(container #%d)', $rowId), $entry['content']);
    }

    public function testDeleteLogsToChangelog(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for delete changelog test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $Links->destroy();

        $entry = $this->latestChangelogEntry($Item, 'container_deleted');
        $this->assertNotNull($entry);
        $this->assertStringContainsString('Removed container', $entry['content']);
        $this->assertStringContainsString(sprintf('(container #%d)', $rowId), $entry['content']);
    }

    public function testDeleteMissingRowIsRejected(): void
    {
        $Item = $this->getFreshItem();
        // a container that is already gone is a 404: a no-op must not read as a successful deletion
        $Links = new Containers2ItemsLinks($Item, PHP_INT_MAX);
        $this->expectException(ResourceNotFoundException::class);
        $Links->destroy();
    }

    public function testDestroyActionOnAMissingRowIsRejected(): void
    {
        $this->setCaptureDeletionReason(1);
        $Item = $this->getFreshItem();
        $Links = new Containers2ItemsLinks($Item, PHP_INT_MAX);
        $this->expectException(ResourceNotFoundException::class);
        $Links->patch(Action::Destroy, array('deletion_reason' => ContainerDeletionReason::Contaminated->value));
    }

    public function testNoOpQtyDoesNotLog(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for no-op qty test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(10.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        // patching to identical values must not create qty/unit changelog entries
        $Links->patch(Action::Update, array('qty_stored' => 10.0, 'qty_unit' => 'mL'));

        $this->assertNull($this->latestChangelogEntry($Item, 'container_qty_changed'));
        $this->assertNull($this->latestChangelogEntry($Item, 'container_unit_changed'));
    }

    public function testDatabaseRoundedQtyDoesNotLogChange(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for rounded qty test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(1.23, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $result = $Links->patch(Action::Update, array('qty_stored' => 1.231));

        $this->assertEquals(1.23, (float) $result['qty_stored']);
        $this->assertNull($this->latestChangelogEntry($Item, 'container_qty_changed'));
    }

    public function testCreateIsRejectedWhenLocationIsFull(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box with capacity 1');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(1.0, 'mL');
        $this->setCapacity($box, 1);

        try {
            $Links->createWithQuantity(1.0, 'mL');
            $this->fail('Expected ImproperActionException was not thrown.');
        } catch (ImproperActionException $e) {
            $this->assertStringContainsString('capacity is 1', $e->getMessage());
            $this->assertStringContainsString('already holds 1', $e->getMessage());
        }

        // the transaction rolled back, so nothing was stored
        $this->assertSame(1, $this->StorageUnits->countContainers($box));
    }

    public function testCreateIsAllowedWhileCapacityHasRoom(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box with capacity 2');
        $this->setCapacity($box, 2);
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(1.0, 'mL');
        $Links->createWithQuantity(1.0, 'mL');
        $this->assertSame(2, $this->StorageUnits->countContainers($box));
    }

    public function testCreateWithCapacityEnforcementDisabledIgnoresFullLocation(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for importer bypass');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(1.0, 'mL');
        $this->setCapacity($box, 1);

        // the CSV importer stores unenforced rather than dropping the container silently
        $Links->createWithQuantity(1.0, 'mL', enforceCapacity: false);
        $this->assertSame(2, $this->StorageUnits->countContainers($box));
    }

    public function testZeroCapacityBlocksStorageEntirely(): void
    {
        $Item = $this->getFreshItem();
        $room = $this->StorageUnits->create('Room with capacity 0');
        $this->setCapacity($room, 0);

        $Links = new Containers2ItemsLinks($Item, $room);
        try {
            $Links->createWithQuantity(1.0, 'mL');
            $this->fail('Expected ImproperActionException was not thrown.');
        } catch (ImproperActionException $e) {
            $this->assertStringContainsString('capacity is zero', $e->getMessage());
        }
        $this->assertSame(0, $this->StorageUnits->countContainers($room));
    }

    public function testMoveIsRejectedWhenDestinationIsFull(): void
    {
        $Item = $this->getFreshItem();
        $source = $this->StorageUnits->create('Source box');
        $full = $this->StorageUnits->create('Full destination box');

        new Containers2ItemsLinks($Item, $full)->createWithQuantity(1.0, 'mL');
        $this->setCapacity($full, 1);
        new Containers2ItemsLinks($Item, $source)->createWithQuantity(2.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        try {
            $Links->patch(Action::Update, array('storage_id' => $full));
            $this->fail('Expected ImproperActionException was not thrown.');
        } catch (ImproperActionException) {
            $this->addToAssertionCount(1);
        }

        // rolled back: the container never left the source
        $this->assertEquals($source, (int) $Links->readOne()['storage_id']);
        $this->assertNull($this->latestChangelogEntry($Item, 'container_moved'));
    }

    public function testNoOpMoveIntoFullLocationIsAllowed(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box at capacity for no-op move');
        new Containers2ItemsLinks($Item, $box)->createWithQuantity(1.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);
        $this->setCapacity($box, 1);

        // moving a container to where it already is must not be counted against itself
        $Links = new Containers2ItemsLinks($Item, $rowId);
        $result = $Links->patch(Action::Update, array('storage_id' => $box));
        $this->assertEquals($box, (int) $result['storage_id']);
    }

    public function testMoveOutOfAFullLocationIsAllowed(): void
    {
        $Item = $this->getFreshItem();
        $full = $this->StorageUnits->create('Full box to drain');
        $elsewhere = $this->StorageUnits->create('Box to drain into');
        $Links = new Containers2ItemsLinks($Item, $full);
        $Links->createWithQuantity(1.0, 'mL');
        $Links->createWithQuantity(1.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);
        // capacity set below the current occupancy: the location must still be drainable
        $this->setCapacity($full, 1);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $result = $Links->patch(Action::Update, array('storage_id' => $elsewhere));
        $this->assertEquals($elsewhere, (int) $result['storage_id']);
        $this->assertSame(1, $this->StorageUnits->countContainers($full));
    }

    public function testDeleteWithReasonLogsIt(): void
    {
        $this->setCaptureDeletionReason(1);
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for deletion reason test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $Links->patch(Action::Destroy, array('deletion_reason' => ContainerDeletionReason::Contaminated->value));

        $entry = $this->latestChangelogEntry($Item, 'container_deleted');
        $this->assertNotNull($entry);
        $this->assertStringEndsWith('(Contaminated)', $entry['content']);
    }

    public function testDeleteWithReasonAndCommentLogsBoth(): void
    {
        $this->setCaptureDeletionReason(1);
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for deletion comment test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $Links->patch(Action::Destroy, array(
            'deletion_reason' => ContainerDeletionReason::Other->value,
            'deletion_comment' => '  spilled in transit  ',
        ));

        $entry = $this->latestChangelogEntry($Item, 'container_deleted');
        $this->assertNotNull($entry);
        $this->assertStringEndsWith('(Other: spilled in transit)', $entry['content']);
    }

    // the changelog cell is escaped when rendered, so the comment is stored exactly as typed:
    // encoding it here too would show the user "A &amp; B" where they wrote "A & B"
    public function testDeleteCommentIsStoredVerbatim(): void
    {
        $this->setCaptureDeletionReason(1);
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for verbatim comment test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $Links->patch(Action::Destroy, array(
            'deletion_reason' => ContainerDeletionReason::Other->value,
            'deletion_comment' => 'batch A & B, <10% left',
        ));

        $entry = $this->latestChangelogEntry($Item, 'container_deleted');
        $this->assertNotNull($entry);
        $this->assertStringEndsWith('(Other: batch A & B, <10% left)', $entry['content']);
    }

    // a bodyless DELETE cannot carry the reason, so it is refused when one is required
    public function testDeleteVerbIsRejectedWhenAReasonIsRequired(): void
    {
        $this->setCaptureDeletionReason(1);
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for missing reason test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        try {
            $Links->destroy();
            $this->fail('Expected ImproperActionException was not thrown.');
        } catch (ImproperActionException $e) {
            // the verb cannot carry a reason, so the message must point somewhere useful
            $this->assertStringContainsString('PATCH', $e->getMessage());
        }
        // the container must still be there
        $this->assertEquals(7.0, $this->readContainerQty('containers2items', $rowId));
        $this->assertNull($this->latestChangelogEntry($Item, 'container_deleted'));
    }

    public function testDestroyActionWithoutReasonIsRejected(): void
    {
        $this->setCaptureDeletionReason(1);
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for empty reason test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        try {
            $Links->patch(Action::Destroy, array());
            $this->fail('Expected ImproperActionException was not thrown.');
        } catch (ImproperActionException $e) {
            // PATCH can carry a reason, so it gets no advice about the verb
            $this->assertStringNotContainsString('PATCH', $e->getMessage());
        }
        $this->assertEquals(7.0, $this->readContainerQty('containers2items', $rowId));
        $this->assertNull($this->latestChangelogEntry($Item, 'container_deleted'));
    }

    // a non numeric reason casts to 0, which matches no case: it must be refused, not mapped onto one
    public function testDestroyActionWithAnInvalidReasonIsRejected(): void
    {
        $this->setCaptureDeletionReason(1);
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for invalid reason test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        // 'contaminated' is what the enum was backed with before it became int-backed
        foreach (array(999, 0, 'banana', 'contaminated') as $reason) {
            try {
                $Links->patch(Action::Destroy, array('deletion_reason' => $reason));
                $this->fail(sprintf('Expected ImproperActionException was not thrown for "%s".', $reason));
            } catch (ImproperActionException) {
                $this->addToAssertionCount(1);
            }
        }
        // nothing was destroyed by any of them
        $this->assertEquals(7.0, $this->readContainerQty('containers2items', $rowId));
        $this->assertNull($this->latestChangelogEntry($Item, 'container_deleted'));
    }

    public function testDeleteWithOtherReasonRequiresAComment(): void
    {
        $this->setCaptureDeletionReason(1);
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for empty comment test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $this->expectException(ImproperActionException::class);
        $Links->patch(Action::Destroy, array('deletion_reason' => ContainerDeletionReason::Other->value));
    }

    public function testDeleteCommentLongerThanTheLimitIsRejected(): void
    {
        $this->setCaptureDeletionReason(1);
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for long comment test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        try {
            $Links->patch(Action::Destroy, array(
                'deletion_reason' => ContainerDeletionReason::Other->value,
                'deletion_comment' => str_repeat('a', 256),
            ));
            $this->fail('Expected ImproperActionException was not thrown.');
        } catch (ImproperActionException) {
            $this->addToAssertionCount(1);
        }
        // refused, not silently shortened
        $this->assertEquals(7.0, $this->readContainerQty('containers2items', $rowId));
        $this->assertNull($this->latestChangelogEntry($Item, 'container_deleted'));
    }

    public function testDeleteCommentAtTheLimitIsAccepted(): void
    {
        $this->setCaptureDeletionReason(1);
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for max comment test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $comment = str_repeat('a', 255);
        $Links = new Containers2ItemsLinks($Item, $rowId);
        $Links->patch(Action::Destroy, array(
            'deletion_reason' => ContainerDeletionReason::Other->value,
            'deletion_comment' => $comment,
        ));

        $entry = $this->latestChangelogEntry($Item, 'container_deleted');
        $this->assertNotNull($entry);
        $this->assertStringEndsWith(sprintf('(Other: %s)', $comment), $entry['content']);
    }

    public function testDeleteWithoutReasonIsFineWhenNotRequired(): void
    {
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for disabled setting test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);

        $Links = new Containers2ItemsLinks($Item, $rowId);
        $Links->destroy();

        $entry = $this->latestChangelogEntry($Item, 'container_deleted');
        $this->assertNotNull($entry);
        $this->assertStringEndsWith(sprintf('(container #%d)', $rowId), $entry['content']);
    }

    public function testCascadeDeleteIgnoresTheReasonRequirement(): void
    {
        $this->setCaptureDeletionReason(1);
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box for cascade test');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');

        $this->assertTrue($Links->destroyAll());
    }

    public function testTheSettingComesFromTheTeamOwningTheEntity(): void
    {
        // the requester stays in team 1, where the setting is off
        $this->setCaptureDeletionReason(0);
        $this->setCaptureDeletionReason(1, 2);
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box owned by another team');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);
        $this->moveItemToTeam($Item, 2);

        // a fresh object, so entityData carries the new team rather than the cached one
        $Reloaded = new Items($Item->Users, $Item->id);
        $this->expectException(ImproperActionException::class);
        new Containers2ItemsLinks($Reloaded, $rowId)->destroy();
    }

    public function testTheSettingIsNotTakenFromTheRequesterTeam(): void
    {
        // the mirror case: on for the requester, off for the team owning the entity
        $this->setCaptureDeletionReason(1);
        $this->setCaptureDeletionReason(0, 2);
        $Item = $this->getFreshItem();
        $box = $this->StorageUnits->create('Box owned by a team without the setting');
        $Links = new Containers2ItemsLinks($Item, $box);
        $Links->createWithQuantity(7.0, 'mL');
        $rowId = $this->latestContainerRowId('containers2items', $Item->id);
        $this->moveItemToTeam($Item, 2);

        $Item = new Items($Item->Users, $Item->id);
        new Containers2ItemsLinks($Item, $rowId)->destroy();

        $entry = $this->latestChangelogEntry($Item, 'container_deleted');
        $this->assertNotNull($entry);
        // no reason suffix: the line stops at the container id
        $this->assertStringEndsWith(sprintf('(container #%d)', $rowId), $entry['content']);
    }

    private function setCapacity(int $storageId, int $capacity): void
    {
        $this->StorageUnits->setId($storageId);
        $this->StorageUnits->patch(Action::Update, array('capacity' => $capacity));
    }

    private function latestChangelogEntry(Items $entity, string $target): ?array
    {
        foreach (new Changelog($entity)->readAll() as $row) {
            if ($row['target'] === $target) {
                return $row;
            }
        }
        return null;
    }

    private function latestContainerRowId(string $table, int $itemId): int
    {
        $Db = Db::getConnection();
        $req = $Db->prepare('SELECT id FROM ' . $table . ' WHERE item_id = :item_id ORDER BY id DESC LIMIT 1');
        $req->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $Db->execute($req);
        return (int) $req->fetchColumn();
    }

    private function setCaptureDeletionReason(int $value, int $team = 1): void
    {
        $Db = Db::getConnection();
        $req = $Db->prepare('UPDATE teams SET capture_container_deletion_reason = :value WHERE id = :team');
        $req->bindValue(':value', $value, PDO::PARAM_INT);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $Db->execute($req);
    }

    private function moveItemToTeam(Items $entity, int $team): void
    {
        $Db = Db::getConnection();
        $req = $Db->prepare('UPDATE items SET team = :team WHERE id = :id');
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $req->bindValue(':id', $entity->id, PDO::PARAM_INT);
        $Db->execute($req);
    }

    private function readContainerQty(string $table, int $id): float
    {
        $Db = Db::getConnection();
        $req = $Db->prepare('SELECT qty_stored FROM ' . $table . ' WHERE id = :id');
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $Db->execute($req);
        return (float) $req->fetchColumn();
    }
}
