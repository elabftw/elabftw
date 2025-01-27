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

use Elabftw\Models\Users;
use League\Csv\Reader;
use League\Csv\Info as CsvInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function arsort;

/**
 * Import a csv file
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
        $csv = Reader::createFromPath($this->UploadedFile->getPathname(), 'r');
        // get stats about the most likely delimiter
        $delimitersCount = CsvInfo::getDelimiterStats($csv, array(',', '|', "\t", ';'), -1);
        // reverse sort the array by value to get the delimiter with highest probability
        arsort($delimitersCount, SORT_NUMERIC);
        // set the delimiter from the first value
        $csv->setDelimiter((string) key($delimitersCount));
        $csv->setHeaderOffset(0);
        return $csv;
    }
}
