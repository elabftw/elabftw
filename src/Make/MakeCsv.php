<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Override;

use function date;

/**
 * Export entities as CSV
 */
final class MakeCsv extends AbstractMakeCsv
{
    public function __construct(private array $entityArr)
    {
        parent::__construct();
        $this->rows = $this->getRows();
    }

    #[Override]
    public function getFileName(): string
    {
        return date('Y-m-d_H-i-s') . '-export.elabftw.csv';
    }

    #[Override]
    protected function getRows(): array
    {
        $rows = array();
        foreach ($this->entityArr as $entity) {
            $rows[] = array(
                'id' => $entity->entityData['id'],
                'date' => $entity->entityData['date'],
                'title' => htmlspecialchars_decode((string) $entity->entityData['title'], ENT_QUOTES | ENT_COMPAT),
                'userid' => $entity->entityData['userid'],
                'fullname' => $entity->entityData['fullname'],
                'body' => html_entity_decode(strip_tags(htmlspecialchars_decode((string) $entity->entityData['body'], ENT_QUOTES | ENT_COMPAT))),
                'category' => (string) $entity->entityData['category'],
                'category_title' => htmlspecialchars_decode((string) $entity->entityData['category_title'], ENT_QUOTES | ENT_COMPAT),
                'category_color' => (string) $entity->entityData['category_color'],
                'status' => (string) $entity->entityData['status'],
                'status_title' => htmlspecialchars_decode((string) $entity->entityData['status_title'], ENT_QUOTES | ENT_COMPAT),
                'status_color' => (string) $entity->entityData['status_color'],
                'custom_id' => $entity->entityData['custom_id'] ?? '',
                'elabid' => $entity->entityData['elabid'] ?? '',
                'rating' => $entity->entityData['rating'],
                'sharelink' => $entity->entityData['sharelink'],
                'metadata' => $entity->entityData['metadata'] ?? '',
                'tags' => $entity->entityData['tags'] ?? '',
            );
        }
        return $rows;
    }
}
