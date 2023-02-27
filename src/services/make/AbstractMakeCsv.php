<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Services;

use Elabftw\Interfaces\StringMakerInterface;
use League\Csv\Reader;
use League\Csv\Writer;

/**
 * Mother class of the Make*Csv services
 */
abstract class AbstractMakeCsv extends AbstractMake implements StringMakerInterface
{
    protected string $contentType = 'text/csv; charset=UTF-8';

    /**
     * Create a CSV file from header and rows
     */
    public function getFileContent(): string
    {
        // load the CSV document from a string
        $csv = Writer::createFromString('');

        // insert the header
        $csv->insertOne($this->getHeader());

        // insert all the records
        $csv->insertAll($this->getRows());

        // add UTF8 BOM
        $csv->setOutputBOM(Reader::BOM_UTF8);

        $content = $csv->toString();
        $this->contentSize = mb_strlen($content);
        return $content;
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
