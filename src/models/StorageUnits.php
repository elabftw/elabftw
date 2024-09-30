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

    public function __construct(private Users $requester, ?int $id = null)
    {
        $this->setId($id);
        $this->Db = Db::getConnection();
    }

    public function getApiPath(): string
    {
        return sprintf('api/v2/storage_units/%d', $this->id ?? 0);
    }

    public function readOne(): array
    {
        // Recursive CTE to find the full path of a specific id
        $sql = "
            WITH RECURSIVE storage_hierarchy AS (
                -- Base case: Start with the given id
                SELECT
                    id,
                    unit_name,
                    parent_id,
                    CAST(unit_name AS CHAR(1000)) AS full_path,
                    0 AS level_depth
                FROM
                    storage_units
                WHERE
                    id = :id  -- Use the provided id as the base case

                UNION ALL

                -- Recursive case: Trace the path upwards by finding parent units
                SELECT
                    parent.id,
                    child.unit_name,
                    parent.parent_id,
                    CAST(CONCAT(parent.unit_name, ' > ', child.full_path) AS CHAR(1000)) AS full_path,
                    child.level_depth + 1
                FROM
                    storage_units AS parent
                INNER JOIN
                    storage_hierarchy AS child ON parent.id = child.parent_id
            )

            -- Get the full path from the root to the given id
            SELECT
                id,
                unit_name,
                full_path,
                parent_id,
                level_depth
            FROM
                storage_hierarchy
            ORDER BY
                level_depth DESC LIMIT 1;  -- This ensures the path is ordered from root to the given id
        ";

        // Prepare and execute the query with the specific id
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);  // Bind the given id to the SQL query
        $req->execute();

        return $this->Db->fetch($req);
    }

    public function readAll(): array
    {
        $sql = "WITH RECURSIVE storage_hierarchy AS (
            -- Base case: Select all top-level units (those with no parent)
            SELECT
                id,
                unit_name,
                parent_id,
                unit_name AS full_path,
                0 AS level_depth,
                (SELECT COUNT(*) FROM storage_units AS su WHERE su.parent_id = storage_units.id) AS children_count
            FROM
                storage_units
            WHERE
                parent_id IS NULL

            UNION ALL

            -- Recursive case: Select child units and append them to the parent's path
            SELECT
                child.id,
                child.unit_name,
                child.parent_id,
                CONCAT(parent.full_path, ' > ', child.unit_name) AS full_path,
                parent.level_depth + 1,
                (SELECT COUNT(*) FROM storage_units AS su WHERE su.parent_id = child.id) AS children_count
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
            full_path,
            parent_id,
            level_depth,
            children_count
        FROM
            storage_hierarchy
        ORDER BY
            parent_id";
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        $all = $req->fetchAll();
        $groupedItems = array();
        foreach ($all as $item) {
            $groupedItems[$item['parent_id']][] = $item;
        }
        return $groupedItems;
    }

    public function readAllForCsv(): array
    {
        $sql = 'SELECT su.id, su.unit_name, su.parent_id, items.title, items.id AS item_id, items.qty_stored, items.qty_unit
            FROM storage_units AS su
            LEFT JOIN items ON (items.storage = su.id)';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function patch(Action $action, array $params): array
    {
        $this->update(new CommentParam($params['comment']));
        return $this->readOne();
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create($reqBody['unit_name'], Filter::intOrNull($reqBody['parent_id']));
    }

    public function create(string $unitName, ?int $parentId = null): int
    {
        $sql = 'INSERT INTO storage_units(parent_id, unit_name) VALUES(:parent_id, :unit_name)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':parent_id', $parentId);
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
        $this->canWriteOrExplode();
        $sql = 'DELETE FROM storage_units WHERE id = :id OR parent_id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
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
