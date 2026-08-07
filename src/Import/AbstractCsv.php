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
use Psr\Log\LoggerInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Links\Containers2ItemsLinks;
use Elabftw\Models\StorageUnits;
use Elabftw\Models\Tags;
use Elabftw\Params\TagParam;

use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function trim;
use function arsort;
use function array_diff_key;
use function array_flip;
use function json_encode;
use function filter_var;
use function key;

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
        protected LoggerInterface $logger,
    ) {
        parent::__construct($requester, $UploadedFile, $logger);
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

    // we remove the columns processed as sql columns to be left with the ones we want in metadata as extra fields
    protected function getColumnsImportableAsMetadata(array $row): array
    {
        $processedColumns = $this->getProcessedColumns();
        return array_diff_key($row, array_flip($processedColumns));
    }

    protected function collectMetadata(array $row): string
    {
        $strippedRow = $this->getColumnsImportableAsMetadata($row);
        if (empty($strippedRow)) {
            return '{}';
        }
        $metadata = array();
        foreach ($strippedRow as $key => $value) {
            $type = 'text';
            // detect a link-looking value to set type to url
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                $type = 'url';
            }
            $metadata['extra_fields'][$key] = array(
                'type' => $type,
                'value' => $value,
            );
        }
        return json_encode($metadata, JSON_THROW_ON_ERROR, 12);
    }

    protected function getMetadataFromRow(array $row): string
    {
        // if an explicit "metadata" column exists on the row, we directly use that and don't care about the rest
        if (!empty($row['metadata'])) {
            return $row['metadata'];
        }
        return $this->collectMetadata($row);
    }

    // collect tags
    protected function processTags(AbstractEntity $entity, array $tags): void
    {
        $Tags = new Tags($entity);
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if ($tag !== '') {
                $Tags->create(new TagParam($tag), true);
            }
        }
    }

    // create storage units with 'location' column
    protected function processLocation(AbstractEntity $entity, array $row, string $locationSplitter = '/'): void
    {
        if (empty($row['location']) || $locationSplitter === '') {
            return;
        }

        $locationSplit = array_values(array_filter(
            array_map('trim', explode($locationSplitter, $row['location'])),
            static fn($location): bool => $location !== '',
        ));

        if ($locationSplit === array()) {
            return;
        }

        $StorageUnits = new StorageUnits($this->requester, requireEditRights: false);
        $storageUnitId = $StorageUnits->createImmutable($locationSplit);
        $Containers2ItemsLinks = new Containers2ItemsLinks($entity, $storageUnitId);

        $Containers2ItemsLinks->createWithQuantity(
            (float) ($row['quantity'] ?? 1.0),
            $row['unit'] ?? '•',
        );
    }
}
