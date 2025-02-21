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

use Override;

/**
 * Make a CSV file with all the stored containers / compounds
 */
final class MakeStoredCompoundsReport extends MakeInventoryReport
{
    #[Override]
    protected function getData(): array
    {
        return $this->storageUnits->readEverythingWithNoLimit();
    }
}
