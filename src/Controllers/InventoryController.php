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

final class InventoryController extends AbstractHtmlController
{
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
                'containersArr' => $containersArr,
                'storageUnitsArr' => $StorageUnits->readAllRecursive(),
            ),
        );
    }
}
