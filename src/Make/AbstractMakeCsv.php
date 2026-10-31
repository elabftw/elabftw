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
use Stringable;

use function array_keys;
use function array_map;
use function in_array;
use function is_numeric;
use function is_string;
use function strlen;

/**
 * Mother class of the Make*Csv services
 */
abstract class AbstractMakeCsv extends AbstractMake implements StringMakerInterface
{
    /**
     * Spreadsheet applications evaluate a cell starting with one of these characters as a formula
     * @see https://owasp.org/www-community/attacks/CSV_Injection
     */
    private const array FORMULA_TRIGGERS = array('=', '+', '-', '@', "\t", "\r");

    protected string $contentType = 'text/csv; charset=UTF-8';

    protected array $rows;

    /**
     * Create a CSV file from header and rows
     */
    #[Override]
    public function getFileContent(): string
    {
        // load the CSV document from a string
        $csv = Writer::fromString('');

        // produce RFC 4180 output: spreadsheet applications do not treat a backslash as an escape character,
        // so if we did, a value containing \" would end the enclosure early and spill into a new cell
        $csv->setEscape('');

        // prevent CSV/formula injection: user content must not be evaluated by the spreadsheet application opening the file
        $csv->addFormatter(self::escapeFormulas(...));

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
     * Prefix with a single quote the cells that would be evaluated as a formula, so they are displayed as text instead
     */
    public static function escapeFormulas(array $record): array
    {
        return array_map(self::escapeCell(...), $record);
    }

    protected function getHeader(): array
    {
        return array_keys($this->rows[0]);
    }

    /**
     * Get all the rows
     */
    abstract protected function getRows(): array;

    private static function escapeCell(mixed $cell): mixed
    {
        if ($cell instanceof Stringable) {
            $cell = (string) $cell;
        }
        // a number such as -20 cannot be a formula: leave it untouched so it is still a number in the spreadsheet
        if (!is_string($cell) || $cell === '' || is_numeric($cell)) {
            return $cell;
        }
        if (in_array($cell[0], self::FORMULA_TRIGGERS, true)) {
            return "'" . $cell;
        }
        return $cell;
    }
}
