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

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Links\Containers2ItemsLinks;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;

class ContainersLinksTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private StorageUnits $StorageUnits;

    protected function setUp(): void
    {
        $this->StorageUnits = new StorageUnits(new Users(1, 1), true);
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

    private function latestContainerRowId(string $table, int $itemId): int
    {
        $Db = \Elabftw\Elabftw\Db::getConnection();
        $req = $Db->prepare('SELECT id FROM ' . $table . ' WHERE item_id = :item_id ORDER BY id DESC LIMIT 1');
        $req->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $Db->execute($req);
        return (int) $req->fetchColumn();
    }

    private function readContainerQty(string $table, int $id): float
    {
        $Db = \Elabftw\Elabftw\Db::getConnection();
        $req = $Db->prepare('SELECT qty_stored FROM ' . $table . ' WHERE id = :id');
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $Db->execute($req);
        return (float) $req->fetchColumn();
    }
}
