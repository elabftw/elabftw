<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Interfaces\StringMakerInterface;
use League\Csv\Bom;
use League\Csv\Writer;
use Override;

use function strlen;

/**
 * Mother class of the Make*Csv services
 */
abstract class AbstractMakeCsv extends AbstractMake implements StringMakerInterface
{
    protected string $contentType = 'text/csv; charset=UTF-8';

    /**
     * Create a CSV file from header and rows
     */
    #[Override]
    public function getFileContent(): string
    {
        // load the CSV document from a string
        $csv = Writer::createFromString('');

        // insert the header
        $csv->insertOne($this->getHeader());

        // insert all the records
        /** @psalm-suppress PossiblyInvalidArgument */
        $csv->insertAll($this->getRows());

        // add UTF8 BOM
        $csv->setOutputBOM(Bom::Utf8);

        $content = $csv->toString();
        // mb_strlen doesn't give correct size
        $this->contentSize = strlen($content);
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
