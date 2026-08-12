<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Links;

use Elabftw\Enums\Action;
use Elabftw\Enums\ContainerDeletionReason;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Metadata as MetadataEnum;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\State;
use Elabftw\Enums\Units;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Changelog;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\StorageUnits;
use Elabftw\Models\Teams;
use Elabftw\Params\ContentParams;
use Override;
use PDO;
use Throwable;

use function array_key_exists;
use function intval;
use function json_encode;
use function mb_strlen;
use function number_format;
use function sprintf;
use function trim;
use function _;

/**
 * All about containers links with entities
 */
abstract class AbstractContainersLinks extends AbstractLinks
{
    // keep in sync with the maxlength attribute on the modal input and maxLength in the api doc
    private const int MAX_DELETION_NOTE_LENGTH = 255;

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
        $StorageUnits = new StorageUnits($this->Entity->Users, false);
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

    // Copy Containers from one entity to another
    #[Override]
    public function duplicate(int $id, int $newId, bool $fromTemplate = false, bool $toTemplate = false): int
    {
        $sourceTable = $fromTemplate ? $this->getTemplateTable() : $this->getTable();
        $targetTable = $toTemplate ? $this->getTemplateTable() : $this->getTable();
        $sql = 'INSERT IGNORE INTO ' . $targetTable . ' (item_id, storage_id, qty_stored, qty_unit)
            SELECT :new_id, storage_id, qty_stored, qty_unit
            FROM ' . $sourceTable . '
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
            // a deletion reason cannot travel on a DELETE, so it comes as a POST with a body
            Action::Destroy => (int) $this->destroyWithReason($reqBody),
            default => throw new ImproperActionException('Invalid action for links create.'),
        };
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $this->Entity->canOrExplode(AccessType::Write);
        $before = $this->readOne();
        if (isset($params['storage_id'])) {
            $this->moveToStorage((int) $params['storage_id']);
        }
        $qtyGiven = array_key_exists('qty_stored', $params) && $params['qty_stored'] !== null && $params['qty_stored'] !== '';
        $unitGiven = array_key_exists('qty_unit', $params) && $params['qty_unit'] !== null && $params['qty_unit'] !== '';
        if ($qtyGiven) {
            $this->update('qty_stored', $params['qty_stored']);
        }
        if ($unitGiven) {
            $this->update('qty_unit', $params['qty_unit']);
        }

        // Compare persisted values after database normalization, such as DECIMAL rounding.
        $after = $this->readOne();
        $beforeQty = number_format((float) $before['qty_stored'], 2, '.', '');
        $afterQty = number_format((float) $after['qty_stored'], 2, '.', '');
        $qtyChanged = $qtyGiven && $afterQty !== $beforeQty;
        $unitChanged = $unitGiven
            && (string) $after['qty_unit'] !== (string) $before['qty_unit'];

        if ($qtyChanged || $unitChanged) {
            $containerId = (int) $after['id'];
            $storagePath = $this->getStoragePath((int) $after['storage_id']);
            if ($qtyChanged) {
                new Changelog($this->Entity)->create(new ContentParams(
                    'container_qty_changed',
                    sprintf(
                        'Quantity changed from "%s" to "%s" at "%s" (container #%d)',
                        $beforeQty,
                        $afterQty,
                        $storagePath,
                        $containerId,
                    ),
                ));
            }
            if ($unitChanged) {
                new Changelog($this->Entity)->create(new ContentParams(
                    'container_unit_changed',
                    sprintf(
                        'Unit changed from "%s" to "%s" at "%s" (container #%d)',
                        $before['qty_unit'],
                        $after['qty_unit'],
                        $storagePath,
                        $containerId,
                    ),
                ));
            }
        }
        return $after;
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
            WHERE main.id = :id AND main.item_id = :item_id;';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    public function update(
        string $column,
        int|float|string $value,
    ): bool {
        if ($column !== 'qty_stored' && $column !== 'qty_unit' && $column !== 'storage_id') {
            throw new ImproperActionException('Invalid update target');
        }
        $sql = sprintf(
            'UPDATE %s SET %s = :value WHERE id = :id AND item_id = :item_id',
            $this->getTable(),
            $column,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':value', $value);

        return $this->Db->execute($req);
    }

    #[Override]
    public function destroy(): bool
    {
        return $this->destroyWithReason(array(), viaDeleteVerb: true);
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

    /**
     * $enforceCapacity is only ever false for bulk importers, which turn one request into
     * many containers and have no way to report a per-container rejection. See the CSV importer.
     */
    public function createWithQuantity(float $qty, string $unit, bool $enforceCapacity = true): int
    {
        $this->Entity->canOrExplode(AccessType::Write);

        // the guard's row lock has to be held until this insert commits, so both live in one transaction
        $this->Db->beginTransaction();
        try {
            if ($enforceCapacity) {
                new StorageUnits($this->Entity->Users, false)->assertHasRoom(
                    $this->id ?? throw new ImproperActionException('Missing storage unit id'),
                );
            }
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

            // INSERT IGNORE inserts nothing on a FK violation; only log a real insert
            if ($req->rowCount() > 0) {
                $containerId = $this->Db->lastInsertId();
                new Changelog($this->Entity)->create(new ContentParams(
                    'container_created',
                    sprintf(
                        'Added container with %s %s at "%s" (container #%d)',
                        number_format((float) $qty, 2, '.', ''),
                        $unit,
                        $this->getStoragePath($this->id),
                        $containerId,
                    ),
                ));
            }
            $this->Db->commit();
        } catch (Throwable $e) {
            $this->Db->rollBack();
            throw $e;
        }

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

    private function destroyWithReason(array $params, bool $viaDeleteVerb = false): bool
    {
        $this->Entity->canOrExplode(AccessType::Write);
        // resolve before the DELETE: a missing reason must not destroy anything
        $reasonSuffix = $this->deletionReasonSuffix($params, $viaDeleteVerb);
        $this->Entity->touch();

        // read details for the changelog before the row is deleted
        $current = null;
        $storagePath = '';
        try {
            $current = $this->readOne();
            $storagePath = $this->getStoragePath((int) $current['storage_id']);
        } catch (ResourceNotFoundException) {
            // already gone: still run the DELETE (idempotent), just skip the changelog
        }

        // the row and its audit entry go together: a lost changelog must not leave a deleted container behind
        $this->Db->beginTransaction();
        try {
            $sql = 'DELETE FROM ' . $this->getTable() . ' WHERE id = :id AND item_id = :item_id';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':id', $this->id, PDO::PARAM_INT);
            $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
            $result = $this->Db->execute($req);

            if ($current !== null && $req->rowCount() > 0) {
                new Changelog($this->Entity)->create(new ContentParams(
                    'container_deleted',
                    sprintf(
                        'Removed container with %s %s from "%s" (container #%d)%s',
                        $current['qty_stored'],
                        $current['qty_unit'],
                        $storagePath,
                        (int) $current['id'],
                        $reasonSuffix,
                    ),
                ));
            }
            $this->Db->commit();
            return $result;
        } catch (Throwable $e) {
            $this->Db->rollBack();
            throw $e;
        }
    }

    /**
     * Reason for deletion, as a suffix to the changelog line. Empty unless the team requires it.
     */
    private function deletionReasonSuffix(array $params, bool $viaDeleteVerb): string
    {
        $teamConfig = new Teams($this->Entity->Users, $this->Entity->Users->team)->teamArr;
        if (empty($teamConfig['capture_container_deletion_reason'])) {
            return '';
        }
        $rawReason = $params['deletion_reason'] ?? null;
        if ($rawReason === null || $rawReason === '') {
            // DELETE has no body to carry a reason: point at the verb that does, and at a stale page reload
            throw new ImproperActionException($viaDeleteVerb
                ? _('A reason is required to delete a container. Use POST with action "destroy", or reload the page.')
                : _('A reason is required to delete a container.'));
        }
        // anything non numeric casts to 0, which matches no case
        $reason = ContainerDeletionReason::tryFrom((int) $rawReason)
            ?? throw new ImproperActionException(_('Invalid reason for deleting a container.'));
        // stored as typed: the changelog cell is escaped when rendered, so purifying here would double encode
        $note = trim((string) ($params['deletion_note'] ?? ''));
        // reject rather than truncate: a silently shortened audit record is worse than a refused deletion
        if (mb_strlen($note) > self::MAX_DELETION_NOTE_LENGTH) {
            throw new ImproperActionException(sprintf(
                _('The note is too long: %d characters maximum.'),
                self::MAX_DELETION_NOTE_LENGTH,
            ));
        }
        if ($reason->requiresNote() && $note === '') {
            throw new ImproperActionException(_('Please describe the reason for deleting this container.'));
        }
        return sprintf(' (%s)', $note === '' ? $reason->toHuman() : $reason->toHuman() . ': ' . $note);
    }

    private function moveToStorage(int $newStorageId): void
    {
        if ($newStorageId <= 0) {
            throw new ImproperActionException('Invalid storage_id');
        }
        $current = $this->readOne();
        $oldStorageId = (int) $current['storage_id'];
        // a no-op move must return before the capacity guard below, or the container
        // would be counted as an occupant of its own destination and a full location
        // would refuse to accept the container already sitting in it
        if ($oldStorageId === $newStorageId) {
            return;
        }

        // resolve destination (enforces existence via readOne) and require inventory edit rights
        $requireInventoryRights = Config::getConfig()->configArr['inventory_require_edit_rights'] === '1';
        $Destination = new StorageUnits($this->Entity->Users, $requireInventoryRights);
        $Destination->setId($newStorageId);
        $Destination->canWriteOrExplode();
        $destinationData = $Destination->readOne();

        // resolve old full_path for the changelog
        $oldPath = $this->getStoragePath($oldStorageId);

        // a move is an add to the destination; the source is never checked, as freeing a slot needs no permission
        $this->Db->beginTransaction();
        try {
            $Destination->assertHasRoom($newStorageId);
            $this->update('storage_id', $newStorageId);
            $this->Entity->touch();

            new Changelog($this->Entity)->create(new ContentParams(
                'container_moved',
                sprintf(
                    'From "%s" to "%s" (container #%d)',
                    $oldPath,
                    $destinationData['full_path'] ?? '',
                    $this->id ?? -1,
                ),
            ));
            $this->Db->commit();
        } catch (Throwable $e) {
            $this->Db->rollBack();
            throw $e;
        }
    }

    // full_path for changelog messages; read-only, so no edit rights required
    private function getStoragePath(int $storageId): string
    {
        $Storage = new StorageUnits($this->Entity->Users, false);
        $Storage->setId($storageId);
        return $Storage->readOne()['full_path'] ?? '';
    }

    /**
     * Copy the links of an item into our entity
     * Also copy links of an experiment into our entity unless it is a template
     */
    private function import(): int
    {
        $this->Entity->canOrExplode(AccessType::Write);

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
