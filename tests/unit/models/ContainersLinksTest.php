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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Links\Containers2ItemsLinks;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

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

    private function latestContainerRowId(string $table, int $itemId): int
    {
        $Db = \Elabftw\Elabftw\Db::getConnection();
        $req = $Db->prepare('SELECT id FROM ' . $table . ' WHERE item_id = :item_id ORDER BY id DESC LIMIT 1');
        $req->bindValue(':item_id', $itemId, \PDO::PARAM_INT);
        $Db->execute($req);
        return (int) $req->fetchColumn();
    }
}
