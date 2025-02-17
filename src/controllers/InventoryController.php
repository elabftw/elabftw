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

use Elabftw\Models\StorageUnits;

use function array_merge;

class InventoryController extends AbstractHtmlController
{
    protected function getTemplate(): string
    {
        return 'inventory.html';
    }

    protected function getPageTitle(): string
    {
        return _('Inventory');
    }

    protected function getData(): array
    {
        $StorageUnits = new StorageUnits($this->app->Users);
        $containersArr = array();
        // only make a query if we do a search
        if ($this->app->Request->query->has('q')) {
            $containersArr = $StorageUnits->readAll($StorageUnits->getQueryParams($this->app->Request->query));
        }
        if ($this->app->Request->query->has('storage_unit')) {
            $containersArr = $StorageUnits->readAllFromStorage($this->app->Request->query->getInt('storage_unit'));
        }
        return array_merge(
            parent::getData(),
            array(
                'containersArr' => $containersArr,
                'storageUnitsArr' => $StorageUnits->readAllRecursive(),
            ),
        );
    }
}
