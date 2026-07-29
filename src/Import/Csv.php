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
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BodyContentType;
use Elabftw\Params\EntityParams;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Users\Users;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Override;

use function array_key_exists;
use function explode;
use function in_array;

/**
 * Import entries from a csv file.
 */
final class Csv extends AbstractCsv
{
    public function __construct(
        protected Users $requester,
        protected UploadedFile $UploadedFile,
        protected LoggerInterface $logger,
        protected EntityType $entityType = EntityType::Items,
        protected ?int $category = null,
        protected BasePermissions $canreadBase = BasePermissions::Team,
        protected BasePermissions $canwriteBase = BasePermissions::User,
        protected string $canread = AbstractEntity::EMPTY_CAN_JSON,
        protected string $canwrite = AbstractEntity::EMPTY_CAN_JSON,
        protected ?int $template = null,
    ) {
        parent::__construct(
            $requester,
            $UploadedFile,
            $logger,
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
                $category = $this->getCategoryId($this->entityType, $row['category_title']);
            }
            $status = empty($row['status_title']) ? null : $this->getStatusId($this->entityType, $row['status_title']);
            $customId = empty($row['custom_id']) ? null : (int) $row['custom_id'];
            // metadata can come from the dedicated metadata column or from extra CSV columns
            $csvMetadata = empty($row['metadata']) ? null : (string) $row['metadata'];
            $columnMetadata = $this->collectMetadata($row);
            $metadata = $csvMetadata ?? $columnMetadata;

            $tags = empty($row['tags']) ? array() : explode(self::TAGS_SEPARATOR, $row['tags']);
            $canreadBase = empty($row['canread_base']) ? $this->canreadBase : $row['canread_base'];
            $canwriteBase = empty($row['canwrite_base']) ? $this->canwriteBase : $row['canwrite_base'];
            $canread = empty($row['canread']) ? $this->canread : $row['canread'];
            $canwrite = empty($row['canwrite']) ? $this->canwrite : $row['canwrite'];

            if ($this->template !== null && in_array(
                $this->entityType,
                array(EntityType::Experiments, EntityType::Items),
                true
            )
            ) {
                $entityId = $entity->postAction(
                    Action::Create,
                    array('template' => $this->template, 'title' => $row['title']),
                );
                $entity->setId($entityId);
                $this->processTags($entity, $tags);
                $this->processLocation($entity, $row);
                // preserve the template metadata schema while applying the explicit metadata payload first
                if ($csvMetadata !== null) {
                    $entity->update(new EntityParams('metadatamerge', $csvMetadata));
                }
                // merge remaining CSV columns as metadata, letting explicit columns override matching fields
                $entity->update(new EntityParams('metadatamerge', $columnMetadata));
            } else {
                $entityId = $entity->create(
                    title: $row['title'],
                    body: $body,
                    date: $date,
                    canreadBase: $canreadBase,
                    canwriteBase: $canwriteBase,
                    canread: $canread,
                    canwrite: $canwrite,
                    tags: $tags,
                    category: $category,
                    status: $status,
                    customId: $customId,
                    metadata: $metadata,
                    rating: (int) ($row['rating'] ?? 0),
                    contentType: BodyContentType::from((int) ($row['contentType'] ?? BodyContentType::Html->value)),
                );
                $entity->setId($entityId);
                // process inventory location after create because location links need the new resource id
                if ($this->entityType === EntityType::Items) {
                    $this->processLocation($entity, $row);
                }
                // when a metadata column exists, create used it first, so merge extra CSV columns afterwards
                if ($csvMetadata !== null) {
                    $entity->update(new EntityParams('metadatamerge', $columnMetadata));
                }
            }

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
            'location',
            'quantity',
            'unit',
        );
    }
}
