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

class StorageUnitsTest extends \PHPUnit\Framework\TestCase
{
    private StorageUnits $StorageUnits;

    protected function setUp(): void
    {
        $this->StorageUnits = new StorageUnits(new Users(1, 1));
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
    }

    public function testReadAll(): void
    {
        $this->assertIsArray($this->StorageUnits->readAll());
        $this->assertIsArray($this->StorageUnits->readAllRecursive());
        $this->assertIsArray($this->StorageUnits->readAllFromStorage(1));
        $this->assertIsArray($this->StorageUnits->readCount());
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/storage_units/', $this->StorageUnits->getApiPath());
    }

    public function testCreateImmutable(): void
    {
        $locations = array('Parent 1', 'Middle 1', '', 'Leaf 1');
        $this->assertEquals(9, $this->StorageUnits->createImmutable($locations));
        // a second time to ensure we get the same number
        $this->assertEquals(9, $this->StorageUnits->createImmutable($locations));
    }
}
