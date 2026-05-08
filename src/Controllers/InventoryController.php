<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Models\Config;
use Elabftw\Models\StorageUnits;
use Override;

use function array_merge;
use function _;
use function array_values;

final class InventoryController extends AbstractHtmlController
{
    /**
     * Collapse the raw SQL output into one row per (page, entity, storage).
     *
     * The recursive query in StorageUnits joins compounds, so a container with N compounds
     * appears as N rows. We first dedupe by container id, then group those containers by
     * (page, entity, storage) and aggregate: container_count, and qty_total when all
     * containers in the group share a unit (qty_mixed_units otherwise).
     */
    public static function aggregateContainers(array $rows): array
    {
        $byContainer = array();
        foreach ($rows as $row) {
            $byContainer[$row['page'] . ':' . $row['container2item_id']] ??= $row;
        }

        $grouped = array();
        foreach ($byContainer as $row) {
            $key = $row['page'] . ':' . $row['entity_id'] . ':' . $row['storage_id'];
            if (!isset($grouped[$key])) {
                $row['container_count'] = 1;
                $row['qty_total'] = $row['qty_stored'];
                $row['qty_mixed_units'] = false;
                $grouped[$key] = $row;
                continue;
            }
            $grouped[$key]['container_count']++;
            if (!$grouped[$key]['qty_mixed_units'] && $grouped[$key]['qty_unit'] === $row['qty_unit']) {
                $grouped[$key]['qty_total'] = (float) $grouped[$key]['qty_total'] + (float) $row['qty_stored'];
            } else {
                $grouped[$key]['qty_mixed_units'] = true;
                $grouped[$key]['qty_total'] = null;
            }
        }
        return array_values($grouped);
    }

    #[Override]
    protected function getTemplate(): string
    {
        return 'inventory.html';
    }

    #[Override]
    protected function getPageTitle(): string
    {
        return _('Inventory');
    }

    #[Override]
    protected function getData(): array
    {
        $StorageUnits = new StorageUnits($this->app->Users, Config::getConfig()->configArr['inventory_require_edit_rights'] === '1');
        $containersArr = array();
        // only make a query if we do a search
        // set the 'limit' parameter for both readAll and readAllFromStorage. see #6116
        $this->app->Request->query->set('limit', 9999);
        if ($this->app->Request->query->has('q')) {
            $containersArr = $StorageUnits->readAll($StorageUnits->getQueryParams($this->app->Request->query));
        }
        if ($this->app->Request->query->has('storage_unit')) {
            $containersArr = $StorageUnits->readAllFromStorage($this->app->Request->query->getInt('storage_unit'));
        }
        return array_merge(
            parent::getData(),
            array(
                'containersArr' => self::aggregateContainers($containersArr),
                'storageUnitsArr' => $StorageUnits->readAllRecursive(),
            ),
        );
    }
}
