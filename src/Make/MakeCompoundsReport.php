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
use Elabftw\Models\Compounds;

use function date;

/**
 * Make a CSV file with all the compounds
 */
class MakeCompoundsReport extends AbstractMakeCsv
{
    protected array $rows;

    public function __construct(protected Compounds $compounds)
    {
        parent::__construct();
        $this->rows = $this->getRows();
    }

    /**
     * Return a nice name for the file
     */
    public function getFileName(): string
    {
        return date('Y-m-d') . '-compounds.elabftw.csv';
    }

    /**
     * Here we populate the first row: it will be the column names
     */
    protected function getHeader(): array
    {
        return array_keys($this->rows[0]);
    }

    protected function getData(): array
    {
        return $this->compounds->readAll();
    }

    /**
     * Generate an array for the requested data
     */
    protected function getRows(): array
    {
        $rows = $this->getData();
        if (empty($rows)) {
            throw new ImproperActionException(_('Nothing to export!'));
        }
        return $rows;
    }
}
