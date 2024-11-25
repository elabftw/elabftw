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

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Params\CommentParam;
use Elabftw\Services\Filter;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

/**
 * All about storage_units
 */
class StorageUnits extends AbstractRest
{
    use SetIdTrait;

    public function __construct(private Users $requester, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    public function getApiPath(): string
    {
        return 'api/v2/storage_units/';
    }

    #[Override]
    public function readOne(): array
    {
        // Recursive CTE to find the full path of a specific id
        $sql = "
            WITH RECURSIVE storage_hierarchy AS (
                -- Base case: Start with the given id
                SELECT
                    id,
                    name,
                    parent_id,
                    CAST(name AS CHAR(1000)) AS full_path,
                    0 AS level_depth
                FROM
                    storage_units
                WHERE
                    id = :id  -- Use the provided id as the base case

                UNION ALL

                -- Recursive case: Trace the path upwards by finding parent units
                SELECT
                    parent.id,
                    child.name,
                    parent.parent_id,
                    CAST(CONCAT(parent.name, ' > ', child.full_path) AS CHAR(1000)) AS full_path,
                    child.level_depth + 1
                FROM
                    storage_units AS parent
                INNER JOIN
                    storage_hierarchy AS child ON parent.id = child.parent_id
            )

            -- Get the full path from the root to the given id
            SELECT
                id,
                name,
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

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = "WITH RECURSIVE storage_hierarchy AS (
            -- Base case: Select all top-level units (those with no parent)
            SELECT
                id,
                name,
                parent_id,
                name AS full_path,
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
                child.name,
                child.parent_id,
                CONCAT(parent.full_path, ' > ', child.name) AS full_path,
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
            name,
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
        $sql = 'SELECT su.id, su.name, su.parent_id, data.qty_stored, data.qty_unit, data.item_id, data.created_at, data.modified_at, data.title
            FROM storage_units AS su
            LEFT JOIN (
                SELECT
                    c2e.storage_id,
                    c2e.item_id,
                    c2e.qty_stored,
                    c2e.qty_unit,
                    c2e.created_at,
                    c2e.modified_at,
                    experiments.title
                FROM containers2experiments AS c2e
                LEFT JOIN experiments ON item_id = experiments.id
                UNION ALL
                SELECT
                    c2i.storage_id,
                    c2i.item_id,
                    c2i.qty_stored,
                    c2i.qty_unit,
                    c2i.created_at,
                    c2i.modified_at,
                    items.title
                FROM containers2items AS c2i
                LEFT JOIN items ON item_id = items.id
            ) AS data ON su.id = data.storage_id ORDER BY su.id ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $this->update(new CommentParam($params['name']));
        return $this->readOne();
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create(
            $reqBody['name'] ?? throw new ImproperActionException('Missing value for "name"'),
            Filter::intOrNull($reqBody['parent_id']),
        );
    }

    public function create(string $unitName, ?int $parentId = null): int
    {
        $sql = 'INSERT INTO storage_units(parent_id, name) VALUES(:parent_id, :name)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':parent_id', $parentId);
        $req->bindParam(':name', $unitName);
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    public function update(CommentParam $params): bool
    {
        $sql = 'UPDATE storage_units SET
            name = :name
            WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':name', $params->getContent());
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    #[Override]
    public function destroy(): bool
    {
        $this->canWriteOrExplode();
        $sql = 'DELETE FROM storage_units WHERE id = :id OR parent_id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }
}
