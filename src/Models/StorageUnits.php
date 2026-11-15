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
use Elabftw\Elabftw\CompoundPermissions;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\Action;
use Elabftw\Enums\GhsSignalWord;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\Users;
use Elabftw\Params\CommentParam;
use Elabftw\Services\Filter;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;
use Throwable;

use function _;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function filter_var;
use function implode;
use function in_array;
use function is_int;
use function is_string;
use function mb_strlen;
use function sprintf;
use function trim;

/**
 * All about storage_units
 */
final class StorageUnits extends AbstractRest
{
    use SetIdTrait;

    // upper bound of the INT UNSIGNED capacity column
    public const int MAX_CAPACITY = 4294967295;

    /**
     * The hazard flags of a compound, for readHazards(): the GHS pictograms first, then the other labels.
     * They are the columns of the compounds table.
     */
    public const array GHS_PICTOGRAM_FLAGS = array(
        'is_explosive',
        'is_flammable',
        'is_oxidising',
        'is_gas_under_pressure',
        'is_corrosive',
        'is_toxic',
        'is_hazardous2health',
        'is_serious_health_hazard',
        'is_hazardous2env',
    );

    public const array OTHER_HAZARD_FLAGS = array(
        'is_radioactive',
        'is_explosive_precursor',
        'is_cmr',
        'is_nano',
        'is_controlled',
        'is_drug',
        'is_drug_precursor',
        'is_antibiotic',
        'is_antibiotic_precursor',
        'is_ed2health',
        'is_ed2env',
        'is_pbt',
        'is_pmt',
        'is_vpvb',
        'is_vpvm',
    );

    /**
     * Tables holding real containers: a row in one of them occupies a slot. The template
     * tables are absent on purpose, as their containers are defaults copied on creation
     * rather than physical stock. Single source of truth for what "occupied" means.
     */
    private const array CONTAINER_TABLES = array('containers2items', 'containers2experiments');

    // width of storage_units.name
    private const int MAX_NAME_LENGTH = 255;

    // how many levels a single import may create: keeps paths far below FULL_PATH_LENGTH
    // and recursion far below MySQL's cte_max_recursion_depth
    private const int MAX_IMPORT_DEPTH = 32;

    // full_path is built by concatenation in recursive CTEs, where MySQL fixes the column
    // type from the anchor member: without a wide cast it inherits VARCHAR(255) from name
    // and any path longer than one name makes the whole (instance-wide) query fail
    private const int FULL_PATH_LENGTH = 16383;

    public function __construct(public Users $requester, private bool $requireEditRights, ?int $id = null)
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
        $req = $this->Db->prepare(sprintf(
            'SELECT id, name, parent_id, capacity, %s AS occupancy FROM storage_units WHERE id = :id',
            self::occupancySql('storage_units.id'),
        ));
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $unit = $this->Db->fetch($req);
        return array_merge($unit, $this->readPaths(array($unit['id']))[$unit['id']]);
    }

    /**
     * The full_path of several storage units in one query, as readOne() gives it
     * Returns an array of full_path indexed by storage unit id
     */
    public function readFullPaths(array $ids): array
    {
        return array_map(static fn(array $path): string => $path['full_path'], $this->readPaths($ids));
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
     * Get containers from a given storage unit and its descendants.
     */
    public function readAllFromStorage(int $storageId): array
    {
        $sql = $this->getRecursiveSql(
            $this->requester->getUserid(),
            $this->requester->getTeam(),
            ' sh.storage_id IN (SELECT storage_id FROM selected_storage_hierarchy)',
            true,
        ) . ' ORDER BY full_path, entity_title';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':storage_id', $storageId, PDO::PARAM_INT);
        $req->bindValue(':userid', $this->requester->getUserid(), PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $queryParams ??= $this->getQueryParams();
        if ($queryParams->getQuery()->getBoolean('hierarchy')) {
            return $this->readHierarchyRows();
        }
        $sql = $this->getRecursiveSql(
            (int) $this->requester->userData['userid'],
            (int) $this->requester->userData['team'],
            '(entity.title LIKE :query OR
            compounds.cas_number LIKE :query OR
            compounds.name LIKE :query OR
            compounds.iupac_name LIKE :query OR
            sh.full_path LIKE :query)',
        ) . ' ORDER BY storage_name, entity_title';

        if ($queryParams->getLimit() > 0) {
            $sql .= sprintf(' LIMIT %d', $queryParams->getLimit());
        }
        $req = $this->Db->prepare($sql);
        $req->bindValue(':query', '%' . $queryParams->getQuery()->getString('q') . '%');
        $req->bindValue(':userid', $this->requester->userid, PDO::PARAM_INT);
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
            items.custom_id AS entity_custom_id,
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
            compounds.is_antibiotic,
            compounds.is_antibiotic_precursor,
            compounds.is_drug,
            compounds.is_drug_precursor,
            compounds.is_explosive_precursor,
            compounds.is_cmr,
            compounds.is_nano,
            compounds.is_controlled,
            compounds.is_ed2health,
            compounds.is_ed2env,
            compounds.is_pbt,
            compounds.is_pmt,
            compounds.is_vpvb,
            compounds.is_vpvm
        FROM
            containers2items AS c2i
        LEFT JOIN storage_units ON c2i.storage_id = storage_units.id
        LEFT JOIN items ON c2i.item_id = items.id
        LEFT JOIN compounds2items ON items.id = compounds2items.entity_id
        LEFT JOIN compounds ON compounds2items.compound_id = compounds.id

        UNION

        SELECT
            storage_units.id AS storage_id,
            storage_units.name AS storage_name,
            experiments.id AS entity_id,
            experiments.title AS entity_title,
            experiments.custom_id AS entity_custom_id,
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
            compounds.is_antibiotic,
            compounds.is_antibiotic_precursor,
            compounds.is_drug,
            compounds.is_drug_precursor,
            compounds.is_explosive_precursor,
            compounds.is_cmr,
            compounds.is_nano,
            compounds.is_controlled,
            compounds.is_ed2health,
            compounds.is_ed2env,
            compounds.is_pbt,
            compounds.is_pmt,
            compounds.is_vpvb,
            compounds.is_vpvm
        FROM
            containers2experiments AS c2e
        LEFT JOIN storage_units ON c2e.storage_id = storage_units.id
        LEFT JOIN experiments ON c2e.item_id = experiments.id
        LEFT JOIN compounds2experiments ON compounds2experiments.entity_id = experiments.id
        LEFT JOIN compounds ON compounds2experiments.compound_id = compounds.id;';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    /**
     * The hazards of what is stored in this location and in the locations under it, for the people who must enter it
     * in an emergency (firefighters): the pictograms and hazard statements of the compounds of the containers.
     * Nothing names a compound or an entry, so the read permissions of the entries do not apply: everyone can see
     * what is dangerous in a room, whoever stored it there.
     */
    public function readHazards(): array
    {
        if ($this->requester instanceof AnonymousUser) {
            throw new ForbiddenException();
        }
        if ($this->id === null) {
            throw new ImproperActionException('Missing id of the storage unit.');
        }
        $location = $this->readOne();

        // one row per container and compound of its entry, or per container alone when its entry has no compound
        $flags = implode(', ', array_map(
            static fn(string $flag): string => 'compounds.' . $flag,
            array_merge(self::GHS_PICTOGRAM_FLAGS, self::OTHER_HAZARD_FLAGS),
        ));
        $parts = array();
        foreach (array('items', 'experiments') as $entityTable) {
            $parts[] = sprintf(
                "SELECT '%1\$s' AS entity_table, containers.id AS container_id, compounds.id AS compound_id, compounds.ghs_signal_word, %2\$s
                FROM containers2%1\$s AS containers
                INNER JOIN location ON (location.id = containers.storage_id)
                INNER JOIN %1\$s AS entity ON (entity.id = containers.item_id AND entity.state != %3\$d)
                LEFT JOIN compounds2%1\$s AS links ON (links.entity_id = entity.id)
                LEFT JOIN compounds ON (compounds.id = links.compound_id AND compounds.state != %3\$d)",
                $entityTable,
                $flags,
                State::Deleted->value,
            );
        }
        $sql = 'WITH RECURSIVE location AS (
                SELECT id FROM storage_units WHERE id = :id
                UNION ALL
                SELECT child.id FROM storage_units AS child INNER JOIN location ON (child.parent_id = location.id)
            ) ' . implode(' UNION ALL ', $parts);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $containers = array();
        $containersWithCompound = array();
        $compoundIds = array();
        $hazards = array();
        $signalWord = null;
        foreach ($req->fetchAll() as $row) {
            $container = $row['entity_table'] . ':' . $row['container_id'];
            $containers[$container] = true;
            if ($row['compound_id'] === null) {
                continue;
            }
            $containersWithCompound[$container] = true;
            $compoundIds[(int) $row['compound_id']] = true;
            foreach (array_merge(self::GHS_PICTOGRAM_FLAGS, self::OTHER_HAZARD_FLAGS) as $flag) {
                if ((int) $row[$flag] === 1) {
                    $hazards[$flag] = true;
                }
            }
            $signalWord = GhsSignalWord::mostSevere($signalWord, GhsSignalWord::tryFrom((string) $row['ghs_signal_word']));
        }

        return array(
            'location' => $location,
            'containers_count' => count($containers),
            'containers_without_compound_count' => count($containers) - count($containersWithCompound),
            'compounds_count' => count($compoundIds),
            'signal_word' => $signalWord?->value,
            'pictograms' => array_values(array_filter(self::GHS_PICTOGRAM_FLAGS, static fn(string $flag): bool => isset($hazards[$flag]))),
            'other_hazards' => array_values(array_filter(self::OTHER_HAZARD_FLAGS, static fn(string $flag): bool => isset($hazards[$flag]))),
            'statements' => $this->readHazardStatements(array_keys($compoundIds)),
        );
    }

    public function readAllRecursive(): array
    {
        $all = $this->readHierarchyRows();
        $groupedItems = array();
        foreach ($all as $item) {
            $groupedItems[$item['parent_id']][] = $item;
        }
        return $groupedItems;
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $this->canWriteOrExplode();
        $hasName = !empty($params['name']);
        $hasParent = array_key_exists('parent_id', $params);
        $hasCapacity = array_key_exists('capacity', $params);
        if (!$hasName && !$hasParent && !$hasCapacity) {
            throw new ImproperActionException('No valid update target provided.');
        }
        $newParentId = null;
        if ($hasParent) {
            $newParentId = $this->normalizeParentId($params['parent_id']);
            $this->validateMoveTarget($newParentId);
        }
        $newCapacity = null;
        if ($hasCapacity) {
            $newCapacity = $this->normalizeCapacity($params['capacity']);
        }
        // one transaction for the lot: a move that fails at the database level must not
        // leave the name or capacity from the same request behind
        $this->Db->beginTransaction();
        try {
            if ($hasName) {
                $this->update(new CommentParam($params['name']));
            }
            if ($hasCapacity) {
                $this->updateCapacity($newCapacity);
            }
            if ($hasParent) {
                $this->writeMove($newParentId);
            }
            $this->Db->commit();
        } catch (Throwable $e) {
            $this->Db->rollBack();
            throw $e;
        }
        return $this->readOne();
    }

    public function move(?int $newParentId): bool
    {
        $this->validateMoveTarget($newParentId);
        return $this->applyMove($newParentId);
    }

    public function readHistory(): array
    {
        $this->canWriteOrExplode();
        $sql = 'SELECT id, old_parent_id, new_parent_id, users_id, created_at
            FROM storage_units_history
            WHERE storage_unit_id = :id
            ORDER BY created_at ASC, id ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function createImmutable(array $locations): int
    {
        if (count($locations) > self::MAX_IMPORT_DEPTH) {
            throw new ImproperActionException(sprintf(_('A location cannot have more than %d levels.'), self::MAX_IMPORT_DEPTH));
        }
        $parent = null;
        $id = 0;

        foreach ($locations as $location) {
            $unitName = trim($location);
            if (empty($unitName)) {
                continue;
            }

            $res = $this->searchStorage($unitName, $parent);
            if ($res) {
                $id = $parent = $res['id'];
            } else {
                $id = $parent = $this->create($unitName, $parent);
            }
        }

        return $id;
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $this->canWriteOrExplode();
        return $this->create(
            $reqBody['name'] ?? throw new ImproperActionException('Missing value for "name"'),
            Filter::intOrNull($reqBody['parent_id'] ?? 0),
            $this->normalizeCapacity($reqBody['capacity'] ?? null),
        );
    }

    public function create(string $unitName, ?int $parentId = null, ?int $capacity = null): int
    {
        $this->assertNameLength($unitName);
        $sql = 'INSERT INTO storage_units(parent_id, name, capacity) VALUES(:parent_id, :name, :capacity)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':parent_id', $parentId);
        $req->bindParam(':name', $unitName);
        $req->bindValue(':capacity', $capacity, $capacity === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    public function update(CommentParam $params): bool
    {
        $this->assertNameLength($params->getContent());
        $sql = 'UPDATE storage_units SET
            name = :name
            WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':name', $params->getContent());
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * How many containers sit directly in a unit. Deliberately unfiltered: a container
     * occupies its slot whether or not the requester can read the entity, and whether or
     * not that entity is in the bin. Binning a record does not empty a freezer.
     */
    public function countContainers(int $storageId): int
    {
        $sql = 'SELECT ' . self::occupancySql(':storage_id');
        $req = $this->Db->prepare($sql);
        $req->bindValue(':storage_id', $storageId, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Refuse if one more container in $storageId would exceed its capacity.
     * Callers must already be in a transaction: the FOR UPDATE lock below is what
     * serializes concurrent writers to the same unit, and it has to be held until
     * their insert commits to be worth anything.
     */
    public function assertHasRoom(int $storageId): void
    {
        // lock only the row that declares the limit, so contention is exactly as wide as the constraint
        $req = $this->Db->prepare('SELECT capacity FROM storage_units WHERE id = :id FOR UPDATE');
        $req->bindValue(':id', $storageId, PDO::PARAM_INT);
        $this->Db->execute($req);
        $capacity = $req->fetchColumn();
        // no such unit: leave it to the foreign key. NULL: unlimited, so never count.
        // a strict comparison matters here, as a capacity of 0 is meaningful and falsy
        if ($capacity === false || $capacity === null) {
            return;
        }
        $capacity = (int) $capacity;
        $occupancy = $this->countContainers($storageId);
        if ($occupancy < $capacity) {
            return;
        }
        $path = new self($this->requester, false, $storageId)->readOne()['full_path'] ?? '';
        if ($capacity === 0) {
            throw new ImproperActionException(sprintf(
                _('Nothing can be stored directly in "%s": its capacity is zero. Pick one of the locations inside it.'),
                $path,
            ));
        }
        throw new ImproperActionException(sprintf(
            _('Cannot store in "%s": its capacity is %d and it already holds %d.'),
            $path,
            $capacity,
            $occupancy,
        ));
    }

    public function updateCapacity(?int $capacity): bool
    {
        $sql = 'UPDATE storage_units SET capacity = :capacity WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':capacity', $capacity, $capacity === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    #[Override]
    public function destroy(bool $recursive = false): bool
    {
        $this->canWriteOrExplode();
        if ($this->hasChildren()) {
            throw new ImproperActionException(_('Cannot delete a storage unit with children!'));
        }
        if ($this->hasContainers()) {
            throw new ImproperActionException(_('Cannot delete a storage unit with containers!'));
        }
        $sql = 'DELETE FROM storage_units WHERE id = :id OR parent_id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    public function canWrite(): bool
    {
        if ($this->requester instanceof AnonymousUser) {
            return false;
        }

        return $this->requester->userData['can_manage_inventory_locations'] === 1 || $this->requireEditRights === false;
    }

    public function canWriteOrExplode(): void
    {
        if (!$this->canWrite()) {
            throw new ForbiddenException();
        }
    }

    /**
     * SQL expression counting the containers stored directly in the unit designated by
     * $idExpression. Shared so the occupancy displayed in the tree and the occupancy the
     * capacity guard enforces are the same number by construction, not by coincidence.
     */
    /**
     * The path from the root of each storage unit, and its depth: the recursive CTE walks up from each unit,
     * and the deepest row of a unit has its whole path
     *
     * @return array<int, array{full_path: string, level_depth: int}> indexed by storage unit id
     */
    private function readPaths(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn(int $id): bool => $id > 0)));
        if (empty($ids)) {
            return array();
        }
        $fullPathLength = self::FULL_PATH_LENGTH;
        $sql = sprintf("
            WITH RECURSIVE storage_hierarchy AS (
                SELECT
                    id AS original_id,
                    parent_id,
                    CAST(name AS CHAR({$fullPathLength})) AS full_path,
                    0 AS level_depth
                FROM
                    storage_units
                WHERE
                    id IN (%s)

                UNION

                SELECT
                    child.original_id,
                    parent.parent_id,
                    CAST(CONCAT(parent.name, ' > ', child.full_path) AS CHAR({$fullPathLength})) AS full_path,
                    child.level_depth + 1
                FROM
                    storage_units AS parent
                INNER JOIN
                    storage_hierarchy AS child ON parent.id = child.parent_id
            )
            SELECT original_id, full_path, level_depth FROM (
                SELECT
                    original_id,
                    full_path,
                    level_depth,
                    ROW_NUMBER() OVER (PARTITION BY original_id ORDER BY level_depth DESC) AS row_num
                FROM storage_hierarchy
            ) AS paths
            WHERE row_num = 1", implode(', ', $ids));

        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $paths = array();
        foreach ($req->fetchAll() as $row) {
            $paths[(int) $row['original_id']] = array('full_path' => $row['full_path'], 'level_depth' => (int) $row['level_depth']);
        }
        return $paths;
    }

    private static function occupancySql(string $idExpression): string
    {
        return implode(' + ', array_map(
            static fn(string $table): string => sprintf('(SELECT COUNT(*) FROM %s WHERE storage_id = %s)', $table, $idExpression),
            self::CONTAINER_TABLES,
        ));
    }

    private function normalizeParentId(mixed $raw): ?int
    {
        if ($raw === null) {
            return null;
        }
        if (is_int($raw)) {
            return $raw === 0 ? null : $raw;
        }
        if (is_string($raw) && filter_var($raw, FILTER_VALIDATE_INT) !== false) {
            $parsed = (int) $raw;
            return $parsed === 0 ? null : $parsed;
        }
        throw new ImproperActionException('Invalid parent_id');
    }

    /**
     * null or an empty string mean "unlimited". 0 is a real capacity: it marks a unit that
     * exists to hold child units rather than containers, such as a building or a room.
     */
    private function normalizeCapacity(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_int($raw) || (is_string($raw) && filter_var($raw, FILTER_VALIDATE_INT) !== false)) {
            $parsed = (int) $raw;
            if ($parsed < 0) {
                throw new ImproperActionException('Capacity cannot be negative.');
            }
            // the column is INT UNSIGNED: reject here rather than let MySQL truncate or error
            if ($parsed > self::MAX_CAPACITY) {
                throw new ImproperActionException(sprintf('Capacity cannot exceed %d.', self::MAX_CAPACITY));
            }
            return $parsed;
        }
        throw new ImproperActionException('Invalid capacity: must be a whole number, or empty for unlimited.');
    }

    private function validateMoveTarget(?int $newParentId): void
    {
        if ($newParentId === null) {
            return;
        }
        if ($newParentId <= 0) {
            throw new ImproperActionException('Invalid parent_id');
        }
        if ($newParentId === $this->id) {
            throw new ImproperActionException('A storage unit cannot be its own parent.');
        }
        // existence check + cycle detection: walk the destination's ancestor chain
        $sql = 'WITH RECURSIVE ancestors AS (
                SELECT id, parent_id FROM storage_units WHERE id = :start
                UNION
                SELECT su.id, su.parent_id
                FROM storage_units AS su
                INNER JOIN ancestors AS a ON su.id = a.parent_id
            )
            SELECT id FROM ancestors';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':start', $newParentId, PDO::PARAM_INT);
        $this->Db->execute($req);
        $ancestorIds = array_map(static fn(array $row): int => (int) $row['id'], $req->fetchAll());
        if (empty($ancestorIds)) {
            throw new ImproperActionException('Destination storage unit does not exist.');
        }
        if (in_array($this->id, $ancestorIds, true)) {
            throw new ImproperActionException('Cannot move a storage unit into one of its descendants.');
        }
    }

    /**
     * Owns a transaction, so it is only for callers that have none. patch() writes a move
     * alongside other columns and drives writeMove() from its own transaction instead.
     */
    private function applyMove(?int $newParentId): bool
    {
        $this->Db->beginTransaction();
        try {
            $ok = $this->writeMove($newParentId);
            $this->Db->commit();
            return $ok;
        } catch (Throwable $e) {
            $this->Db->rollBack();
            throw $e;
        }
    }

    // the move itself, with no transaction of its own: the caller must provide one
    private function writeMove(?int $newParentId): bool
    {
        $oldParentId = $this->readCurrentParentId();
        if ($oldParentId === $newParentId) {
            return true;
        }
        $sql = 'UPDATE storage_units SET parent_id = :parent_id WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':parent_id', $newParentId, $newParentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $ok = $this->Db->execute($req);
        $this->recordMove($oldParentId, $newParentId);
        return $ok;
    }

    private function readCurrentParentId(): ?int
    {
        $sql = 'SELECT parent_id FROM storage_units WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $row = $req->fetch();
        if ($row === false) {
            throw new ResourceNotFoundException();
        }
        return $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
    }

    private function recordMove(?int $oldParentId, ?int $newParentId): void
    {
        $sql = 'INSERT INTO storage_units_history
            (storage_unit_id, old_parent_id, new_parent_id, users_id)
            VALUES (:id, :old, :new, :uid)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindValue(':old', $oldParentId, $oldParentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $req->bindValue(':new', $newParentId, $newParentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $req->bindValue(':uid', $this->requester->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    private function assertNameLength(string $unitName): void
    {
        if (mb_strlen($unitName) > self::MAX_NAME_LENGTH) {
            throw new ImproperActionException(sprintf(_('Input is too long. Maximum length is %d characters.'), self::MAX_NAME_LENGTH));
        }
    }

    private function readHierarchyRows(): array
    {
        // counted in the final projection, not in the CTE: the recursion does not need it,
        // so this way it costs two indexed lookups per unit instead of two per branch
        $occupancy = self::occupancySql('storage_hierarchy.id');
        $fullPathLength = self::FULL_PATH_LENGTH;
        $sql = "WITH RECURSIVE storage_hierarchy AS (
            -- Base case: Select all top-level units (those with no parent)
            SELECT
                id,
                name,
                parent_id,
                capacity,
                CAST(name AS CHAR({$fullPathLength})) AS full_path,
                0 AS level_depth,
                (SELECT COUNT(*) FROM storage_units AS su WHERE su.parent_id = storage_units.id) AS children_count
            FROM
                storage_units
            WHERE
                parent_id IS NULL

            UNION

            -- Recursive case: Select child units and append them to the parent's path
            SELECT
                child.id,
                child.name,
                child.parent_id,
                child.capacity,
                CAST(CONCAT(parent.full_path, ' > ', child.name) AS CHAR({$fullPathLength})) AS full_path,
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
            capacity,
            {$occupancy} AS occupancy,
            level_depth,
            children_count
        FROM
            storage_hierarchy
        ORDER BY
            storage_hierarchy.name, parent_id";
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    /**
     * The hazard statements (H and EUH, not the precautionary ones) of these compounds, each once
     *
     * @param list<int> $compoundIds
     */
    private function readHazardStatements(array $compoundIds): array
    {
        if (empty($compoundIds)) {
            return array();
        }
        // the ids are ints from the database: safe to inline, and there can be many
        $sql = sprintf(
            "SELECT DISTINCT gs.code, gs.kind, gs.statement
            FROM compounds2ghs_statements AS c2gs
            INNER JOIN ghs_statements AS gs ON (gs.id = c2gs.statement_id)
            WHERE c2gs.compound_id IN (%s) AND gs.kind != 'P'
            ORDER BY gs.kind, gs.code",
            implode(', ', array_map('intval', $compoundIds)),
        );
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    private function hasContainers(): bool
    {
        $sql = 'SELECT
          IF(
            EXISTS (SELECT 1 FROM containers2experiments            WHERE storage_id = :storage_id)
         OR EXISTS (SELECT 1 FROM containers2experiments_templates  WHERE storage_id = :storage_id)
         OR EXISTS (SELECT 1 FROM containers2items                  WHERE storage_id = :storage_id)
         OR EXISTS (SELECT 1 FROM containers2items_types            WHERE storage_id = :storage_id),
            1,
            0
          ) AS has_container';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':storage_id', $this->id, PDO::PARAM_INT);

        $this->Db->execute($req);
        return (bool) $req->fetchColumn();
    }

    private function hasChildren(): bool
    {
        $sql = 'SELECT id FROM storage_units WHERE parent_id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        $this->Db->execute($req);
        return (bool) $req->fetchColumn();
    }

    private function searchStorage(string $unitName, ?int $parentId): array|false
    {
        if ($parentId === null) {
            $sql = 'SELECT id, parent_id FROM storage_units WHERE name = :name AND parent_id IS NULL';
        } else {
            $sql = 'SELECT id, parent_id FROM storage_units WHERE name = :name AND parent_id = :parent_id';
        }

        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $unitName);

        if ($parentId !== null) {
            $req->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
        }

        $this->Db->execute($req);

        try {
            return $this->Db->fetch($req);
        } catch (ResourceNotFoundException) {
            return false;
        }
    }

    private function getRecursiveSql(
        int $userid,
        int $team,
        string $discriminator,
        bool $includeStorageDescendants = false,
    ): string {
        $CanSqlBuilder = new CanSqlBuilder($this->requester, AccessType::Read);
        $canFilter = $CanSqlBuilder->getCanFilter();
        // a compound has its own permissions: reading the entry is not enough to read the compounds linked to it.
        // In the join, so the container is still listed, without the compound (and the search cannot match it)
        $compoundCan = new CompoundPermissions($this->requester)->getCanCondition('compounds');
        $fullPathLength = self::FULL_PATH_LENGTH;
        $selectedStorageHierarchy = $includeStorageDescendants
            ? ', selected_storage_hierarchy AS (
                SELECT id AS storage_id
                FROM storage_units
                WHERE id = :storage_id

                UNION ALL

                SELECT child.id
                FROM storage_units AS child
                INNER JOIN selected_storage_hierarchy AS parent
                    ON child.parent_id = parent.storage_id
            )'
            : '';
        return sprintf(
            "WITH RECURSIVE storage_hierarchy AS (
                SELECT
                    su.id AS storage_id,
                    su.name AS storage_name,
                    su.parent_id,
                    CAST(su.name AS CHAR({$fullPathLength})) AS full_path
                FROM storage_units AS su
                WHERE su.parent_id IS NULL -- Root-level units

                UNION

                SELECT
                    su.id AS storage_id,
                    su.name AS storage_name,
                    su.parent_id,
                    CAST(CONCAT(parent.full_path, ' > ', su.name) AS CHAR({$fullPathLength})) AS full_path
                FROM storage_units AS su
                INNER JOIN storage_hierarchy AS parent ON su.parent_id = parent.storage_id
            )
            %s

                SELECT
                    entity.id AS entity_id,
                    entity.title AS entity_title,
                    entity.custom_id AS entity_custom_id,
                    'database' AS page,
                    c2i.id AS container2item_id,
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
                    compounds.is_antibiotic,
                    compounds.is_antibiotic_precursor,
                    compounds.is_drug,
                    compounds.is_drug_precursor,
                    compounds.is_explosive_precursor,
                    compounds.is_cmr,
                    compounds.is_nano,
                    compounds.is_controlled,
                    compounds.is_ed2health,
                    compounds.is_ed2env,
                    compounds.is_pbt,
                    compounds.is_pmt,
                    compounds.is_vpvb,
                    compounds.is_vpvm
                FROM
                    containers2items AS c2i
                LEFT JOIN
                    storage_hierarchy AS sh ON c2i.storage_id = sh.storage_id
                LEFT JOIN
                    items AS entity ON c2i.item_id = entity.id
                LEFT JOIN
                    compounds2items ON entity.id = compounds2items.entity_id
                LEFT JOIN
                    compounds ON (compounds2items.compound_id = compounds.id AND %s)
                LEFT JOIN
                    users2teams ON (users2teams.users_id = %d AND users2teams.teams_id = %d)
                LEFT JOIN
                    teams ON (entity.team = teams.id)
                LEFT JOIN
                    users AS u ON (u.userid = entity.userid)
                WHERE
                    -- can sql AND query or storage_id
                    1=1 AND entity.state IN (1,2) AND %s %s

            UNION
                SELECT
                    entity.id AS entity_id,
                    entity.title AS entity_title,
                    entity.custom_id AS entity_custom_id,
                    'experiments' AS page,
                    c2e.id AS container2experiment_id,
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
                    compounds.is_antibiotic,
                    compounds.is_antibiotic_precursor,
                    compounds.is_drug,
                    compounds.is_drug_precursor,
                    compounds.is_explosive_precursor,
                    compounds.is_cmr,
                    compounds.is_nano,
                    compounds.is_controlled,
                    compounds.is_ed2health,
                    compounds.is_ed2env,
                    compounds.is_pbt,
                    compounds.is_pmt,
                    compounds.is_vpvb,
                    compounds.is_vpvm
                FROM
                    containers2experiments AS c2e
                LEFT JOIN
                    storage_hierarchy AS sh ON c2e.storage_id = sh.storage_id
                LEFT JOIN
                    experiments AS entity ON c2e.item_id = entity.id
                LEFT JOIN
                    compounds2experiments ON compounds2experiments.entity_id = c2e.item_id
                LEFT JOIN
                    compounds ON (compounds2experiments.compound_id = compounds.id AND %s)
                LEFT JOIN
                    users2teams ON (users2teams.users_id = %d AND users2teams.teams_id = %d)
                LEFT JOIN
                    teams ON (entity.team = teams.id)
                LEFT JOIN
                    users AS u ON (u.userid = entity.userid)
                WHERE
                    -- can sql AND query or storage_id
                    1=1 AND entity.state IN (1,2) AND %s %s",
            $selectedStorageHierarchy,
            $compoundCan,
            $userid,
            $team,
            $discriminator,
            $canFilter,
            $compoundCan,
            $userid,
            $team,
            $discriminator,
            $canFilter,
        );
    }
}
