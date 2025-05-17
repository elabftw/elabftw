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
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Metadata as MetadataEnum;
use Elabftw\Enums\State;
use Elabftw\Enums\Units;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Override;
use PDO;

use function intval;
use function json_encode;

/**
 * All about containers links with entities
 */
abstract class AbstractContainersLinks extends AbstractLinks
{
    #[Override]
    public function getApiPath(): string
    {
        return sprintf('%s%d/%s/', $this->Entity->getApiPath(), $this->Entity->id ?? '', $this->getTable());
    }

    /**
     * Get links for an entity
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        // do not ORDER BY entity.date as items_types don't have date column
        $sql = 'SELECT
            main.id,
            main.qty_stored,
            main.qty_unit,
            main.storage_id,
            main.item_id,
            main.created_at,
            main.modified_at,
            storage_units.id AS storage_id,
            storage_units.name AS storage_name
            FROM ' . $this->getTable() . ' AS main
            LEFT JOIN ' . $this->getTargetType()->value . ' AS entity ON (main.item_id = entity.id)
            LEFT JOIN storage_units ON (main.storage_id = storage_units.id)
            WHERE main.item_id = :item_id AND entity.state IN (:state_normal, :state_archived)
            ORDER by main.created_at ASC, entity.title ASC';


        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':state_normal', State::Normal->value, PDO::PARAM_INT);
        $req->bindValue(':state_archived', State::Archived->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        $results = $req->fetchAll();
        // Note: currently it's easier to loop on the storage and do a readOne() rather than include the full_path here
        $StorageUnits = new StorageUnits($this->Entity->Users);
        foreach ($results as &$result) {
            $StorageUnits->setId($result['storage_id']);
            $result['full_path'] = $StorageUnits->readOne()['full_path'];
        }
        return $results;
    }

    /**
     * Get related entities
     */
    #[Override]
    public function readRelated(): array
    {
        $sql = 'SELECT
            main.id,
            main.qty_stored,
            main.qty_unit,
            main.storage_id,
            main.item_id,
            main.created_at,
            main.modified_at,
            storage_units.id AS storage_id,
            storage_units.name AS storage_name
            FROM ' . $this->getTable() . ' AS main
            LEFT JOIN ' . $this->getTargetType()->value . ' AS entity ON (entity_links.item_id = entity.id)
            LEFT JOIN ' . $this->getCatTable() . ' AS categoryt ON (entity.category = categoryt.id)
            LEFT JOIN ' . $this->getStatusTable() . ' AS statust ON (entity.status = statust.id)';

        $sql .= sprintf('WHERE entity_links.link_id = :id AND (entity.state = %d OR entity.state = %d) ORDER by', State::Normal->value, State::Archived->value);

        $sql .= ' categoryt.title ASC, entity.title ASC';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    /**
     * Copy the links from one entity to an other
     *
     * @param int $id The id of the original entity
     * @param int $newId The id of the new entity that will receive the links
     * @param bool $fromTpl do we duplicate from template?
     */
    #[Override]
    public function duplicate(int $id, int $newId, $fromTpl = false): int
    {
        $table = $this->getTable();
        if ($fromTpl) {
            $table = $this->getTemplateTable();
        }
        $sql = 'INSERT IGNORE INTO ' . $this->getTable() . ' (item_id, link_id)
            SELECT :new_id, link_id
            FROM ' . $table . '
            WHERE item_id = :old_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':new_id', $newId, PDO::PARAM_INT);
        $req->bindParam(':old_id', $id, PDO::PARAM_INT);

        return (int) $this->Db->execute($req);
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        return match ($action) {
            Action::Create => $this->createWithQuantity((float) $reqBody['qty_stored'], $reqBody['qty_unit'] ?? Units::Unit->value),
            Action::Duplicate => $this->import(),
            default => throw new ImproperActionException('Invalid action for links create.'),
        };
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        //$this->canOrExplode(AccessType::Write);
        if ($params['qty_stored']) {
            $this->update('qty_stored', $params['qty_stored']);
        }
        if ($params['qty_unit']) {
            $this->update('qty_unit', $params['qty_unit']);
        }
        return $this->readOne();
    }

    #[Override]
    public function readOne(): array
    {
        $sql = 'SELECT
            main.id,
            main.qty_stored,
            main.qty_unit,
            main.storage_id,
            main.item_id,
            main.created_at,
            main.modified_at
            FROM ' . $this->getTable() . ' AS main
            WHERE main.id = :id;';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    public function update(
        string $column,
        int|string $value,
    ): bool {
        if ($column !== 'qty_stored' && $column !== 'qty_unit') {
            throw new ImproperActionException('Invalid update target');
        }
        $sql = sprintf(
            'UPDATE %s SET %s = :value WHERE id = :id',
            $this->getTable(),
            $column,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':value', $value);

        return $this->Db->execute($req);
    }

    #[Override]
    public function destroy(): bool
    {
        $this->Entity->canOrExplode('write');
        $this->Entity->touch();

        $sql = 'DELETE FROM ' . $this->getTable() . ' WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function destroyAll(): bool
    {
        $sql = 'DELETE FROM ' . $this->getTable() . ' WHERE item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    #[Override]
    public function isSelfLinkViaMetadata(string $extraFieldKey, string $targetId): bool
    {
        // get the extra field type for the given key
        // build json path to field type
        $jsonPath = sprintf(
            '$.%s.%s.type',
            MetadataEnum::ExtraFields->value,
            json_encode($extraFieldKey, JSON_HEX_APOS | JSON_THROW_ON_ERROR)
        );
        $sql = sprintf(
            "SELECT metadata->>'%s' FROM %s WHERE id = :id",
            $jsonPath,
            $this->Entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $extraFieldType = $req->fetchColumn();

        return $this->Entity->entityType->value === $extraFieldType
            && $this->Entity->id === intval($targetId);
    }

    public function createWithQuantity(float $qty, string $unit): int
    {
        $this->Entity->touch();

        // use IGNORE to avoid failure due to a key constraint violations
        $sql = 'INSERT IGNORE INTO ' . $this->getTable() . ' (item_id, storage_id, qty_stored, qty_unit)
            VALUES(:item_id, :storage, :qty_stored, :qty_unit)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':storage', $this->id, PDO::PARAM_INT);
        $req->bindParam(':qty_stored', $qty);
        $req->bindParam(':qty_unit', $unit);

        $this->Db->execute($req);

        return $this->id;
    }

    #[Override]
    abstract protected function getTargetType(): EntityType;

    #[Override]
    abstract protected function getCatTable(): string;

    #[Override]
    abstract protected function getStatusTable(): string;

    #[Override]
    abstract protected function getTable(): string;

    #[Override]
    abstract protected function getImportTargetTable(): string;

    #[Override]
    protected function getOtherImportTypeTable(): string
    {
        return '';
    }

    #[Override]
    protected function getOtherImportTargetTable(): string
    {
        return '';
    }

    #[Override]
    protected function getTemplateTable(): string
    {
        if ($this->Entity instanceof Items || $this->Entity instanceof ItemsTypes) {
            return 'containers2items_types';
        }
        return 'containers2experiments_templates';
    }

    #[Override]
    protected function getRelatedTable(): string
    {
        if ($this->Entity instanceof Experiments) {
            return 'containers2experiments';
        }
        return 'containers2items';
    }

    /**
     * Copy the links of an item into our entity
     * Also copy links of an experiment into our entity unless it is a template
     */
    private function import(): int
    {
        $this->Entity->canOrExplode('write');

        // the :item_id of the SELECT will be the same for all rows: our current entity id
        // use IGNORE to avoid failure due to a key constraint violations
        $sql = 'INSERT IGNORE INTO ' . $this->getTable() . ' (item_id, link_id)
            SELECT :item_id, link_id
            FROM ' . $this->getImportTargetTable() . '
            WHERE item_id = :link_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);

        return (int) $this->Db->execute($req);
    }
}
