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

use Elabftw\Exceptions\IllegalActionException;

use Elabftw\Models\AbstractEntity;

use function date;

/**
 * Make a CSV file from a list of id and a type
 */
class MakeCsv extends AbstractMakeCsv
{
    public function __construct(AbstractEntity $entity, private array $idArr)
    {
        parent::__construct($entity);
    }

    /**
     * Return a nice name for the file
     */
    public function getFileName(): string
    {
        return date('Y-m-d') . '-export.elabftw.csv';
    }

    /**
     * Here we populate the first row: it will be the column names
     */
    protected function getHeader(): array
    {
        return array('id', 'date', 'title', 'body', 'category', 'category_title', 'category_color', 'status', 'status_title', 'status_color', 'custom_id', 'elabid', 'rating', 'url', 'metadata', 'tags');
    }

    /**
     * Generate an array for the requested data
     */
    protected function getRows(): array
    {
        $rows = array();
        foreach ($this->idArr as $id) {
            try {
                $this->Entity->setId((int) $id);
                $permissions = $this->Entity->getPermissions();
            } catch (IllegalActionException) {
                continue;
            }
            if ($permissions['read']) {
                $row = array(
                    $this->Entity->entityData['id'],
                    $this->Entity->entityData['date'],
                    htmlspecialchars_decode((string) $this->Entity->entityData['title'], ENT_QUOTES | ENT_COMPAT),
                    html_entity_decode(strip_tags(htmlspecialchars_decode((string) $this->Entity->entityData['body'], ENT_QUOTES | ENT_COMPAT))),
                    (string) $this->Entity->entityData['category'],
                    htmlspecialchars_decode((string) $this->Entity->entityData['category_title'], ENT_QUOTES | ENT_COMPAT),
                    (string) $this->Entity->entityData['category_color'],
                    (string) $this->Entity->entityData['status'],
                    htmlspecialchars_decode((string) $this->Entity->entityData['status_title'], ENT_QUOTES | ENT_COMPAT),
                    (string) $this->Entity->entityData['status_color'],
                    $this->Entity->entityData['custom_id'] ?? '',
                    $this->Entity->entityData['elabid'] ?? '',
                    $this->Entity->entityData['rating'],
                    $this->getUrl(),
                    $this->Entity->entityData['metadata'] ?? '',
                    $this->Entity->entityData['tags'] ?? '',
                );
                $rows[] = $row;
            }
        }
        return $rows;
    }
}
