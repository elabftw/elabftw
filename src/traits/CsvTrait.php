<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Traits;

use League\Csv\Reader;
use League\Csv\Writer;

/**
 * For producing CSV files
 */
trait CsvTrait
{
    /**
     * Create a CSV file from header and rows
     */
    public function getCsv(): string
    {
        // load the CSV document from a string
        $csv = Writer::createFromString('');

        // insert the header
        $csv->insertOne($this->getHeader());

        // insert all the records
        $csv->insertAll($this->getRows());

        // add UTF8 BOM
        $csv->setOutputBOM(Reader::BOM_UTF8);

        return $csv->toString();
    }

    /**
     * Get the column names
     */
    abstract protected function getHeader(): array;

    /**
     * Get all the rows
     */
    abstract protected function getRows(): array;
}
