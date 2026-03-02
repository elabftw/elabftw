<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Import;

use Elabftw\Models\Users\Users;
use League\Csv\Reader;
use League\Csv\Info as CsvInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Override;

use function arsort;
use function array_diff_key;
use function array_flip;
use function json_encode;
use function filter_var;

/**
 * Parent class for processing a CSV file during import
 */
abstract class AbstractCsv extends AbstractImport
{
    /**
     * @var Reader<array> $reader
     */
    protected Reader $reader;

    protected array $allowedMimes = array(
        'application/csv',
        'application/vnd.ms-excel',
        'text/plain',
        'text/csv',
        'text/tsv',
    );

    public function __construct(
        protected Users $requester,
        protected UploadedFile $UploadedFile,
    ) {
        parent::__construct($requester, $UploadedFile);
        $this->reader = $this->preProcess();
    }

    #[Override]
    public function getCount(): int
    {
        return $this->reader->count();
    }

    /**
     * @return Reader<array>
     */
    protected function preProcess(): Reader
    {
        // we directly read from temporary uploaded file location and do not need to use the cache folder as no extraction is necessary for a .csv
        $csv = Reader::from($this->UploadedFile->getPathname());
        // get stats about the most likely delimiter
        $delimitersCount = CsvInfo::getDelimiterStats($csv, array(',', '|', "\t", ';'), -1);
        // reverse sort the array by value to get the delimiter with highest probability
        arsort($delimitersCount, SORT_NUMERIC);
        // set the delimiter from the first value
        $csv->setDelimiter((string) key($delimitersCount));
        $csv->setHeaderOffset(0);
        return $csv;
    }

    abstract protected function getProcessedColumns(): array;

    protected function collectMetadata(array $row): string
    {
        // we remove the columns present in compound to be left with the ones we want in metadata as extra fields
        $processedColumns = $this->getProcessedColumns();
        $strippedRow = array_diff_key($row, array_flip($processedColumns));
        if (empty($strippedRow)) {
            return '{}';
        }
        $metadata = array();
        foreach ($strippedRow as $key => $value) {
            $type = 'text';
            // translate urls into links
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                $type = 'url';
            }
            $metadata['extra_fields'][$key] = array('value' => $value, 'type' => $type);
        }
        return json_encode($metadata, JSON_THROW_ON_ERROR, 12);

    }
}
