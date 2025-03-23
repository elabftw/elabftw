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

use Elabftw\Elabftw\CanSqlBuilder;
use Elabftw\Enums\AccessType;
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
final class StorageUnits extends AbstractRest
{
    use SetIdTrait;

    public function __construct(private Users $requester, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
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

        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->execute();

        return $this->Db->fetch($req);
    }

    public function readCount(): array
    {
        $sql = 'SELECT
            (SELECT COUNT(id) FROM containers2experiments) AS experiments,
            (SELECT COUNT(id) FROM containers2items) AS items';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetch();

    }

    /**
     * Get containers from a given storage unit id
     */
    public function readAllFromStorage(int $storageId): array
    {
        $sql = $this->getRecursiveSql(
            (int) $this->requester->userData['userid'],
            (int) $this->requester->userData['team'],
            ' sh.storage_id = :storage_id',
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':storage_id', $storageId, PDO::PARAM_INT);
        $req->execute();
        return $req->fetchAll();
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $queryParams ??= $this->getQueryParams();
        $sql = $this->getRecursiveSql(
            (int) $this->requester->userData['userid'],
            (int) $this->requester->userData['team'],
            '(entity.title LIKE :query OR
            compounds.cas_number LIKE :query OR
            compounds.name LIKE :query OR
            compounds.iupac_name LIKE :query OR
            sh.full_path LIKE :query)',
        ) . sprintf(
            ' ORDER BY storage_id, entity_title LIMIT %d',
            $queryParams->getLimit(),
        );

        $req = $this->Db->prepare($sql);
        $req->bindValue(':query', '%' . $queryParams->getQuery()->getString('q') . '%');
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readEverythingWithNoLimit(): array
    {

        $sql = '

        SELECT
            storage_units.id AS storage_id,
            storage_units.name AS storage_name,
            items.id AS entity_id,
            items.title AS entity_title,
            c2i.qty_stored,
            c2i.qty_unit,
            compounds.cas_number,
            compounds.pubchem_cid,
            compounds.is_corrosive,
            compounds.is_serious_health_hazard,
            compounds.is_explosive,
            compounds.is_flammable,
            compounds.is_gas_under_pressure,
            compounds.is_hazardous2env,
            compounds.is_hazardous2health,
            compounds.is_oxidising,
            compounds.is_toxic,
            compounds.is_radioactive,
            compounds.is_antibiotic_precursor,
            compounds.is_drug_precursor,
            compounds.is_explosive_precursor,
            compounds.is_cmr,
            compounds.is_nano,
            compounds.is_controlled
        FROM
            containers2items AS c2i
        LEFT JOIN storage_units ON c2i.storage_id = storage_units.id
        LEFT JOIN items ON c2i.item_id = items.id
        LEFT JOIN compounds2items ON items.id = compounds2items.entity_id
        LEFT JOIN compounds ON compounds2items.compound_id = compounds.id

        UNION ALL

        SELECT
            storage_units.id AS storage_id,
            storage_units.name AS storage_name,
            experiments.id AS entity_id,
            experiments.title AS entity_title,
            c2e.qty_stored,
            c2e.qty_unit,
            compounds.cas_number,
            compounds.pubchem_cid,
            compounds.is_corrosive,
            compounds.is_serious_health_hazard,
            compounds.is_explosive,
            compounds.is_flammable,
            compounds.is_gas_under_pressure,
            compounds.is_hazardous2env,
            compounds.is_hazardous2health,
            compounds.is_oxidising,
            compounds.is_toxic,
            compounds.is_radioactive,
            compounds.is_antibiotic_precursor,
            compounds.is_drug_precursor,
            compounds.is_explosive_precursor,
            compounds.is_cmr,
            compounds.is_nano,
            compounds.is_controlled
        FROM
            containers2experiments AS c2e
        LEFT JOIN storage_units ON c2e.storage_id = storage_units.id
        LEFT JOIN experiments ON c2e.item_id = experiments.id
        LEFT JOIN compounds2experiments ON experiments.id = c2e.item_id
        LEFT JOIN compounds ON compounds2experiments.compound_id = compounds.id;';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function readAllRecursive(): array
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
        //$this->canWriteOrExplode();
        $sql = 'DELETE FROM storage_units WHERE id = :id OR parent_id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    private function getRecursiveSql(int $userid, int $team, string $discriminator): string
    {
        $CanSqlBuilder = new CanSqlBuilder($this->requester, AccessType::Read);
        $canFilter = $CanSqlBuilder->getCanFilter();
        return sprintf(
            "WITH RECURSIVE storage_hierarchy AS (
                SELECT
                    su.id AS storage_id,
                    su.name AS storage_name,
                    su.parent_id,
                    su.name AS full_path
                FROM storage_units AS su
                WHERE su.parent_id IS NULL -- Root-level units

                UNION ALL

                SELECT
                    su.id AS storage_id,
                    su.name AS storage_name,
                    su.parent_id,
                    CONCAT(parent.full_path, ' > ', su.name) AS full_path
                FROM storage_units AS su
                INNER JOIN storage_hierarchy AS parent ON su.parent_id = parent.storage_id
            )

                SELECT
                    entity.id AS entity_id,
                    entity.title AS entity_title,
                    'database' AS page,
                    c2i.qty_stored,
                    c2i.qty_unit,
                    c2i.created_at,
                    c2i.modified_at,
                    u.firstname,
                    u.lastname,
                    CONCAT(u.firstname, ' ', u.lastname) AS fullname,
                    teams.name AS team_name,
                    teams.id AS team_id,
                    sh.storage_id AS storage_id,
                    sh.storage_name,
                    sh.full_path,
                    compounds.cas_number,
                    compounds.pubchem_cid,
                    compounds.is_corrosive,
                    compounds.is_serious_health_hazard,
                    compounds.is_explosive,
                    compounds.is_flammable,
                    compounds.is_gas_under_pressure,
                    compounds.is_hazardous2env,
                    compounds.is_hazardous2health,
                    compounds.is_oxidising,
                    compounds.is_toxic,
                    compounds.is_radioactive,
                    compounds.is_antibiotic_precursor,
                    compounds.is_drug_precursor,
                    compounds.is_explosive_precursor,
                    compounds.is_cmr,
                    compounds.is_nano,
                    compounds.is_controlled
                FROM
                    containers2items AS c2i
                LEFT JOIN
                    storage_hierarchy AS sh ON c2i.storage_id = sh.storage_id
                LEFT JOIN
                    items AS entity ON c2i.item_id = entity.id
                LEFT JOIN
                    compounds2items ON entity.id = compounds2items.entity_id
                LEFT JOIN
                    compounds ON compounds2items.compound_id = compounds.id
                LEFT JOIN
                    users2teams ON (users2teams.users_id = %d AND users2teams.teams_id = %d)
                LEFT JOIN
                    teams ON (entity.userid = teams.id)
                LEFT JOIN
                    users AS u ON (u.userid = entity.userid)
                WHERE
                    -- can sql AND query or storage_id
                    1=1 %s AND %s

            UNION ALL
                SELECT
                    entity.id AS entity_id,
                    entity.title AS entity_title,
                    'experiments' AS page,
                    c2e.qty_stored,
                    c2e.qty_unit,
                    c2e.created_at,
                    c2e.modified_at,
                    u.firstname,
                    u.lastname,
                    CONCAT(u.firstname, ' ', u.lastname) AS fullname,
                    teams.name AS team_name,
                    teams.id AS team_id,
                    sh.storage_id AS storage_id,
                    sh.storage_name,
                    sh.full_path,
                    compounds.cas_number,
                    compounds.pubchem_cid,
                    compounds.is_corrosive,
                    compounds.is_serious_health_hazard,
                    compounds.is_explosive,
                    compounds.is_flammable,
                    compounds.is_gas_under_pressure,
                    compounds.is_hazardous2env,
                    compounds.is_hazardous2health,
                    compounds.is_oxidising,
                    compounds.is_toxic,
                    compounds.is_radioactive,
                    compounds.is_antibiotic_precursor,
                    compounds.is_drug_precursor,
                    compounds.is_explosive_precursor,
                    compounds.is_cmr,
                    compounds.is_nano,
                    compounds.is_controlled
                FROM
                    containers2experiments AS c2e
                LEFT JOIN
                    storage_hierarchy AS sh ON c2e.storage_id = sh.storage_id
                LEFT JOIN
                    experiments AS entity ON c2e.item_id = entity.id
                LEFT JOIN
                    compounds2experiments ON entity.id = c2e.item_id
                LEFT JOIN
                    compounds ON compounds2experiments.compound_id = compounds.id
                LEFT JOIN
                    users2teams ON (users2teams.users_id = %d AND users2teams.teams_id = %d)
                LEFT JOIN
                    teams ON (entity.team = teams.id)
                LEFT JOIN
                    users AS u ON (u.userid = entity.userid)
                WHERE
                    -- can sql AND query or storage_id
                    1=1 %s AND %s",
            $userid,
            $team,
            $canFilter,
            $discriminator,
            $userid,
            $team,
            $canFilter,
            $discriminator,
        );
    }
}
