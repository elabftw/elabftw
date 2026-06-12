<?php

declare(strict_types=1);

/**
 * @author Jonathan Griffiths
 * @copyright 2026 Jonathan Griffiths
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

class InventoryControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testAggregateSingleContainerPassesThrough(): void
    {
        $rows = array($this->row(page: 'database', container2item_id: 1, entity_id: 10, storage_id: 100, qty_stored: '10', qty_unit: 'mL'));

        $out = InventoryController::aggregateContainers($rows);

        $this->assertCount(1, $out);
        $this->assertSame(1, $out[0]['container_count']);
        $this->assertSame('10', $out[0]['qty_total']);
        $this->assertFalse($out[0]['qty_mixed_units']);
    }

    public function testAggregateDedupesMultiCompoundRows(): void
    {
        // Same container2item_id appearing twice (one row per compound from the SQL UNION join)
        $rows = array(
            $this->row(page: 'database', container2item_id: 1, entity_id: 10, storage_id: 100, qty_stored: '10', qty_unit: 'mL'),
            $this->row(page: 'database', container2item_id: 1, entity_id: 10, storage_id: 100, qty_stored: '10', qty_unit: 'mL'),
        );

        $out = InventoryController::aggregateContainers($rows);

        $this->assertCount(1, $out);
        $this->assertSame(1, $out[0]['container_count']);
        $this->assertSame('10', $out[0]['qty_total']);
    }

    public function testAggregateSumsMatchingUnits(): void
    {
        $rows = array(
            $this->row(page: 'database', container2item_id: 1, entity_id: 10, storage_id: 100, qty_stored: '10', qty_unit: 'mL'),
            $this->row(page: 'database', container2item_id: 2, entity_id: 10, storage_id: 100, qty_stored: '5', qty_unit: 'mL'),
            $this->row(page: 'database', container2item_id: 3, entity_id: 10, storage_id: 100, qty_stored: '2.5', qty_unit: 'mL'),
        );

        $out = InventoryController::aggregateContainers($rows);

        $this->assertCount(1, $out);
        $this->assertSame(3, $out[0]['container_count']);
        $this->assertSame(17.5, $out[0]['qty_total']);
        $this->assertSame('mL', $out[0]['qty_unit']);
        $this->assertFalse($out[0]['qty_mixed_units']);
    }

    public function testAggregateMixedUnitsSetsFlagAndNullsTotal(): void
    {
        $rows = array(
            $this->row(page: 'database', container2item_id: 1, entity_id: 10, storage_id: 100, qty_stored: '10', qty_unit: 'mL'),
            $this->row(page: 'database', container2item_id: 2, entity_id: 10, storage_id: 100, qty_stored: '5', qty_unit: 'g'),
        );

        $out = InventoryController::aggregateContainers($rows);

        $this->assertCount(1, $out);
        $this->assertSame(2, $out[0]['container_count']);
        $this->assertNull($out[0]['qty_total']);
        $this->assertTrue($out[0]['qty_mixed_units']);
    }

    public function testAggregateSeparatesItemsAndExperimentsWithSamePk(): void
    {
        // container2item_id=1 from items and from experiments are different containers — page disambiguates
        $rows = array(
            $this->row(page: 'database', container2item_id: 1, entity_id: 10, storage_id: 100, qty_stored: '10', qty_unit: 'mL'),
            $this->row(page: 'experiments', container2item_id: 1, entity_id: 10, storage_id: 100, qty_stored: '5', qty_unit: 'mL'),
        );

        $out = InventoryController::aggregateContainers($rows);

        $this->assertCount(2, $out);
        foreach ($out as $r) {
            $this->assertSame(1, $r['container_count']);
        }
    }

    public function testAggregateProducesOneRowPerEntityStorageGroup(): void
    {
        $rows = array(
            // Entity 10 in storage 100: 2 containers
            $this->row(page: 'database', container2item_id: 1, entity_id: 10, storage_id: 100, qty_stored: '10', qty_unit: 'mL'),
            $this->row(page: 'database', container2item_id: 2, entity_id: 10, storage_id: 100, qty_stored: '5', qty_unit: 'mL'),
            // Entity 10 in storage 101: 1 container (different location)
            $this->row(page: 'database', container2item_id: 3, entity_id: 10, storage_id: 101, qty_stored: '20', qty_unit: 'mL'),
            // Entity 11 in storage 100: 1 container (different entity)
            $this->row(page: 'database', container2item_id: 4, entity_id: 11, storage_id: 100, qty_stored: '1', qty_unit: 'g'),
        );

        $out = InventoryController::aggregateContainers($rows);

        $this->assertCount(3, $out);
        $byKey = array();
        foreach ($out as $r) {
            $byKey[$r['entity_id'] . ':' . $r['storage_id']] = $r;
        }
        $this->assertSame(2, $byKey['10:100']['container_count']);
        $this->assertSame(15.0, $byKey['10:100']['qty_total']);
        $this->assertSame(1, $byKey['10:101']['container_count']);
        $this->assertSame(1, $byKey['11:100']['container_count']);
    }

    public function testAggregateEmptyInput(): void
    {
        $this->assertSame(array(), InventoryController::aggregateContainers(array()));
    }

    /**
     * Build a minimal row matching the shape returned by StorageUnits::getRecursiveSql().
     * Only the fields aggregateContainers reads are populated; the rest is empty so
     * the test breaks loudly if the helper starts depending on additional columns.
     */
    private function row(
        string $page,
        int $container2item_id,
        int $entity_id,
        int $storage_id,
        string $qty_stored,
        string $qty_unit,
    ): array {
        return array(
            'page' => $page,
            'container2item_id' => $container2item_id,
            'entity_id' => $entity_id,
            'entity_title' => 'whatever',
            'storage_id' => $storage_id,
            'full_path' => 'Lab > Box',
            'qty_stored' => $qty_stored,
            'qty_unit' => $qty_unit,
        );
    }
}
