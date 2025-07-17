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

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\StorageUnits;
use Override;

use function date;

/**
 * Make a CSV file with all the inventory
 */
class MakeInventoryReport extends AbstractMakeCsv
{
    protected array $rows;

    public function __construct(protected StorageUnits $storageUnits)
    {
        parent::__construct();
        $this->rows = $this->getRows();
    }

    /**
     * Return a nice name for the file
     */
    #[Override]
    public function getFileName(): string
    {
        return date('Y-m-d') . '-storage.elabftw.csv';
    }

    protected function getData(): array
    {
        return $this->storageUnits->readAll();
    }

    /**
     * Generate an array for the requested data
     */
    #[Override]
    protected function getRows(): array
    {
        $rows = $this->getData();
        if (empty($rows)) {
            throw new ImproperActionException(_('Nothing to export!'));
        }
        return $rows;
    }
}
