<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Import;

use DateTimeImmutable;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users;
use League\Csv\Info as CsvInfo;
use League\Csv\Reader;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Import entries from a csv file.
 */
class Csv extends AbstractImport
{
    protected array $allowedMimes = array(
        'application/csv',
        'application/vnd.ms-excel',
        'text/plain',
        'text/csv',
        'text/tsv',
    );

    public function __construct(
        protected Users $requester,
        protected string $canread,
        protected string $canwrite,
        protected UploadedFile $UploadedFile,
        protected LoggerInterface $logger,
        protected EntityType $entityType = EntityType::Items,
        private bool $dryRun = false,
        protected ?int $category = null,
    ) {
        parent::__construct(
            $requester,
            $UploadedFile,
        );
        if ($dryRun) {
            $this->logger->info('Running in dry-mode: nothing will be imported.');
        }
        // we might have been forced to cast to int a null value, so bring it back to null
        if ($this->category === 0) {
            $this->category = null;
        }
    }

    /**
     * Do the work
     *
     * @throws ImproperActionException
     */
    public function import(): int
    {
        $reader = $this->preProcess();
        if ($this->dryRun) {
            return $reader->count();
        }
        // now loop the rows and do the import
        foreach ($reader->getRecords() as $row) {
            // fail hard if no title column can be found, or we end up with a bunch of Untitled entries
            if (empty($row['title'])) {
                throw new ImproperActionException('Could not find the title column!');
            }
            $entity = $this->entityType->toInstance($this->requester);
            $body = $this->getBodyFromRow($row);
            $date = empty($row['date']) ? null : new DateTimeImmutable($row['date']);
            $category = $this->category;
            // use the category_title of the row only if we didn't specify a category
            if (array_key_exists('category_title', $row) && $this->category === null) {
                $category = $this->getCategoryId($this->entityType, $this->requester, $row['category_title']);
            }
            $status = empty($row['status_title']) ? null : $this->getStatusId($this->entityType, $row['status_title']);
            $customId = empty($row['custom_id']) ? null : (int) $row['custom_id'];
            $metadata = empty($row['metadata']) ? null : (string) $row['metadata'];
            $tags = empty($row['tags']) ? array() : explode(self::TAGS_SEPARATOR, $row['tags']);
            $canread = empty($row['canread']) ? $this->canread : $row['canread'];
            $canwrite = empty($row['canwrite']) ? $this->canwrite : $row['canwrite'];

            $entity->create(
                title: $row['title'],
                body: $body,
                canread: $canread,
                canwrite: $canwrite,
                date: $date,
                tags: $tags,
                template: $category,
                // use template and category so it works for items and experiments
                category: $category,
                status: $status,
                customId: $customId,
                metadata: $metadata,
            );

            $this->inserted++;
        }
        return $this->getInserted();
    }

    /**
     * @return Reader<array>
     */
    private function preProcess(): Reader
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

    /**
     * Generate a body from a row. Add column name and content after that.
     *
     * @param array<string, null|string> $row row from the csv
     */
    private function getBodyFromRow(array $row): string
    {
        // if there is a row called "body", use that instead
        if (array_key_exists('body', $row)) {
            return $row['body'] ?? '';
        }
        // get rid of rows that are processed as columns
        unset($row['title']);
        unset($row['tags']);
        unset($row['metadata']);
        unset($row['category']);
        unset($row['category_title']);
        unset($row['category_color']);
        unset($row['status']);
        unset($row['status_title']);
        unset($row['status_color']);
        unset($row['id']);
        unset($row['custom_id']);
        unset($row['elabid']);
        unset($row['date']);
        unset($row['rating']);
        // deal with the rest of the columns
        $body = '';
        foreach ($row as $subheader => $content) {
            $contentEscaped = htmlspecialchars($content ??= '');
            // translate urls into links
            if (filter_var($content, FILTER_VALIDATE_URL)) {
                $contentEscaped = sprintf('<a href="%1$s">%1$s</a>', $contentEscaped);
            }
            $body .= sprintf('<p>%s: %s</p>', htmlspecialchars($subheader), $contentEscaped);
        }

        return $body;
    }
}
