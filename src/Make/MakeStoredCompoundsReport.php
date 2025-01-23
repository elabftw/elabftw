<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

/**
 * Make a CSV file with all the stored containers / compounds
 */
class MakeStoredCompoundsReport extends MakeInventoryReport
{
    protected function getData(): array
    {
        return $this->storageUnits->readEverythingWithNoLimit();
    }
}
