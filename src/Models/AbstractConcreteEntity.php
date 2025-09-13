<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Params\EntityParams;
use Override;
use PDO;

/**
 * An entity like Experiments or Items. Concrete as opposed to TemplateEntity for experiments templates or items types
 */
abstract class AbstractConcreteEntity extends AbstractEntity
{
    #[Override]
    public function patch(Action $action, array $params): array
    {
        match ($action) {
            Action::SetNextCustomId => $this->update(new EntityParams('custom_id', $this->getNextIdempotentCustomId())),
            default => parent::patch($action, $params),
        };
        return $this->readOne();
    }

    /**
     * Count the number of timestamp archives created during past month (sliding window)
     * Here we merge bloxberg and trusted timestamp methods because there is no way currently to tell them apart
     */
    public function getTimestampLastMonth(): int
    {
        $sql = "SELECT COUNT(id) FROM uploads WHERE comment LIKE 'Timestamp archive%' = 1 AND created_at > (NOW() - INTERVAL 1 MONTH)";
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    protected function getNextCustomId(?int $category): ?int
    {
        if ($category === null) {
            return null;
        }
        $res = $this->getCurrentHighestCustomId($category);
        if ($res === 0) {
            return null;
        }
        return $res + 1;
    }

    private function getCurrentHighestCustomId(int $category): int
    {
        $sql = 'SELECT custom_id FROM ' . $this->entityType->value . ' WHERE category = :category AND custom_id IS NOT NULL ORDER BY custom_id DESC LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':category', $category, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    // figure out the next custom id for our entity
    private function getNextIdempotentCustomId(): int
    {
        if ($this->entityData['category'] === null) {
            throw new ImproperActionException(_('A category is required to fetch the next Custom ID'));
        }
        // start by setting our current custom_id null to get idempotency
        $this->update(new EntityParams('custom_id', null));
        return $this->getCurrentHighestCustomId($this->entityData['category']) + 1;
    }
}
