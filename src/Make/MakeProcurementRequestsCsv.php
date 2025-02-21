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
use Elabftw\Models\ProcurementRequests;
use Override;

use function date;

/**
 * Make a CSV file from the team's procurement requests
 */
final class MakeProcurementRequestsCsv extends AbstractMakeCsv
{
    private array $rows;

    public function __construct(ProcurementRequests $procurementRequests)
    {
        $this->rows = $procurementRequests->readAll();
        if (empty($this->rows)) {
            throw new ImproperActionException(_('Nothing to export!'));
        }
    }

    /**
     * Return a nice name for the file
     */
    #[Override]
    public function getFileName(): string
    {
        return date('Y-m-d') . '-procurement-requests.elabftw.csv';
    }

    /**
     * Here we populate the first row: it will be the column names
     */
    #[Override]
    protected function getHeader(): array
    {
        return array_keys($this->rows[0]);
    }

    /**
     * Generate an array for the requested data
     */
    #[Override]
    protected function getRows(): array
    {
        return $this->rows;
    }
}
