<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\CommentParam;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\Filter;
use Elabftw\Traits\SetIdTrait;
use PDO;

/**
 * All about storage_units
 */
class StorageUnits implements RestInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(private Users $requester)
    {
        $this->Db = Db::getConnection();
    }

    public function getApiPath(): string
    {
        return sprintf('api/v2/storage_units');
    }

    public function readOne(): array
    {
        return array();
    }

    public function readAll(): array
    {
        $sql = "WITH RECURSIVE storage_hierarchy AS (
            -- Base case: Select all top-level units (those with no parent)
            SELECT
                id,
                unit_name,
                level_name,
                parent_id,
                CONCAT(level_name, ': ', unit_name) AS full_path,
                0 AS level_depth
            FROM
                storage_units
            WHERE
                parent_id IS NULL

            UNION ALL

            -- Recursive case: Select child units and append them to the parent's path
            SELECT
                child.id,
                child.unit_name,
                child.level_name,
                child.parent_id,
                CONCAT(parent.full_path, ' > ', child.level_name, ': ', child.unit_name) AS full_path,
                parent.level_depth + 1
            FROM
                storage_units AS child
            INNER JOIN 
                storage_hierarchy AS parent
            ON
                child.parent_id = parent.id
        )

        -- Query to view the full hierarchy
        SELECT
            id,
            unit_name,
            level_name,
            full_path,
            parent_id,
            level_depth
        FROM
            storage_hierarchy
        ORDER BY
            parent_id";
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        $all = $req->fetchAll();
        $groupedItems = [];
        foreach ($all as $item) {
            $groupedItems[$item['parent_id']][] = $item;
        }
        return $groupedItems;
    }

    public function patch(Action $action, array $params): array
    {
        $this->update(new CommentParam($params['comment']));
        return $this->readOne();
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create(Filter::intOrNull($reqBody['parent_id']), $reqBody['level_name'], $reqBody['unit_name']);
    }

    public function create(?int $parentId, string $levelName, string $unitName): int
    {
        $sql = 'INSERT INTO storage_units(parent_id, level_name, unit_name) VALUES(:parent_id, :level_name, :unit_name)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':parent_id', $parentId);
        $req->bindParam(':level_name', $levelName);
        $req->bindParam(':unit_name', $unitName);
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    public function update(CommentParam $params): bool
    {
        return true;
        /*
        $this->Entity->canOrExplode('read');
        $this->canWriteOrExplode();
        $sql = 'UPDATE ' . $this->Entity->entityType->value . '_comments SET
            comment = :content
            WHERE id = :id AND userid = :userid AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
         */
    }

    public function destroy(): bool
    {
        return true;
        /*
        $this->canWriteOrExplode();
        $sql = 'DELETE FROM ' . $this->Entity->entityType->value . '_comments WHERE id = :id AND userid = :userid AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
         */
    }

    protected function canWriteOrExplode(): void
    {
        return;
        /*
        $comment = $this->readOne();
        if ($comment['immutable'] === 1) {
            throw new ImproperActionException(Tools::error(true));
        }
         */
    }

}
