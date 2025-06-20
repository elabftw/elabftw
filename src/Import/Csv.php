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
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Override;

/**
 * Import entries from a csv file.
 */
final class Csv extends AbstractCsv
{
    public function __construct(
        protected Users $requester,
        protected string $canread,
        protected string $canwrite,
        protected UploadedFile $UploadedFile,
        protected LoggerInterface $logger,
        protected EntityType $entityType = EntityType::Items,
        protected ?int $category = null,
    ) {
        parent::__construct(
            $requester,
            $UploadedFile,
        );
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
    #[Override]
    public function import(): int
    {
        $entity = $this->entityType->toInstance($this->requester);
        foreach ($this->reader->getRecords() as $row) {
            // fail hard if no title column can be found, or we end up with a bunch of Untitled entries
            if (empty($row['title'])) {
                throw new ImproperActionException('Could not find the title column!');
            }
            $body = null;
            if (array_key_exists('body', $row) && !empty($row['body'])) {
                $body = $row['body'];
            }
            $date = empty($row['date']) ? null : new DateTimeImmutable($row['date']);
            $category = $this->category;
            // use the category_title of the row only if we didn't specify a category
            if (array_key_exists('category_title', $row) && $this->category === null) {
                $category = $this->getCategoryId($this->entityType, $this->requester, $row['category_title']);
            }
            $status = empty($row['status_title']) ? null : $this->getStatusId($this->entityType, $row['status_title']);
            $customId = empty($row['custom_id']) ? null : (int) $row['custom_id'];
            $contentType = empty($row['content_type']) ? null : (int) $row['content_type'];
            $metadata = empty($row['metadata']) ? null : (string) $row['metadata'];
            if ($metadata === null) {
                $metadata = $this->collectMetadata($row);
            }
            $tags = empty($row['tags']) ? array() : explode(self::TAGS_SEPARATOR, $row['tags']);
            $canread = empty($row['canread']) ? $this->canread : $row['canread'];
            $canwrite = empty($row['canwrite']) ? $this->canwrite : $row['canwrite'];

            $entity->create(
                title: $row['title'],
                body: $body,
                canread: $canread,
                canwrite: $canwrite,
                contentType: $contentType,
                date: $date,
                tags: $tags,
                template: $category,
                // use template and category so it works for items and experiments
                category: $category,
                status: $status,
                customId: $customId,
                metadata: $metadata,
                rating: (int) ($row['rating'] ?? 0),
            );

            $this->inserted++;
        }
        return $this->getInserted();
    }

    #[Override]
    protected function getProcessedColumns(): array
    {
        return array(
            'body',
            'canread',
            'canwrite',
            'category',
            'category_title',
            'custom_id',
            'content_type',
            'date',
            'metadata',
            'rating',
            'status',
            'status_title',
            'tags',
            'title',
        );
    }
}
