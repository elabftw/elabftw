<?php

/**
 * @author Moustapha <Deltablot>
 * @author Nicolas CARPi <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\AccessType;
use Elabftw\Enums\Action;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Params\ContentParams;
use Elabftw\Services\Filter;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

use function array_column;
use function array_key_exists;
use function count;
use function is_array;
use function sprintf;

/**
 * Groups used to organize entity uploads.
 */
final class UploadGroups extends AbstractRest
{
    use SetIdTrait;

    public function __construct(public AbstractEntity $Entity, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('%s%d/upload_groups/', $this->Entity->getApiPath(), $this->Entity->id ?? 0);
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = 'SELECT * FROM upload_groups WHERE entity_id = :entity_id AND entity_type = :entity_type ORDER BY ordering, id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':entity_type', $this->Entity->entityType->toInt(), PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    #[Override]
    public function readOne(): array
    {
        $sql = 'SELECT * FROM upload_groups WHERE id = :id AND entity_id = :entity_id AND entity_type = :entity_type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':entity_type', $this->Entity->entityType->toInt(), PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        if ($action !== Action::Create) {
            throw new ImproperActionException('Invalid action for upload groups.');
        }
        $this->Entity->canOrExplode(AccessType::Write);
        $groups = $this->readAll();
        if ($groups === array()) {
            $this->initializeDefaultUploadOrdering();
        }
        $title = Filter::title((string) ($reqBody['title'] ?? ''));
        $ordering = count($groups) + 1;
        $sql = 'INSERT INTO upload_groups (entity_id, entity_type, title, ordering) VALUES (:entity_id, :entity_type, :title, :ordering)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':entity_type', $this->Entity->entityType->toInt(), PDO::PARAM_INT);
        $req->bindValue(':title', $title);
        $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
        $this->Db->execute($req);
        $id = $this->Db->lastInsertId();
        $this->Entity->touch();
        new Changelog($this->Entity)->create(new ContentParams('upload_groups', Action::Create->value));
        return $id;
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        if ($action !== Action::Update) {
            throw new ImproperActionException('Invalid action for upload groups.');
        }
        $this->Entity->canOrExplode(AccessType::Write);
        if ($this->id === null) {
            if (!array_key_exists('ordering', $params) || !is_array($params['ordering'])) {
                throw new ImproperActionException('Invalid upload group ordering.');
            }
            $this->updateOrdering($params['ordering']);
            $this->Entity->touch();
            new Changelog($this->Entity)->create(new ContentParams('upload_groups', Action::Update->value));
            return $this->readAll();
        }

        if (!array_key_exists('title', $params)) {
            throw new ImproperActionException('Invalid parameter for upload groups.');
        }
        $title = Filter::title((string) $params['title']);
        $sql = 'UPDATE upload_groups SET title = :title WHERE id = :id AND entity_id = :entity_id AND entity_type = :entity_type';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':title', $title);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':entity_type', $this->Entity->entityType->toInt(), PDO::PARAM_INT);
        $this->Db->execute($req);
        $this->Entity->touch();
        new Changelog($this->Entity)->create(new ContentParams('upload_groups', Action::Update->value));
        return $this->readOne();
    }

    #[Override]
    public function destroy(bool $recursive = false): bool
    {
        $this->Entity->canOrExplode(AccessType::Write);
        $this->readOne();

        $uploadSql = 'UPDATE uploads SET group_id = NULL WHERE item_id = :entity_id AND type = :entity_type AND group_id = :group_id';
        $uploadReq = $this->Db->prepare($uploadSql);
        $uploadReq->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $uploadReq->bindValue(':entity_type', $this->Entity->entityType->value);
        $uploadReq->bindParam(':group_id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($uploadReq);

        $sql = 'DELETE FROM upload_groups WHERE id = :id AND entity_id = :entity_id AND entity_type = :entity_type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':entity_type', $this->Entity->entityType->toInt(), PDO::PARAM_INT);
        $result = $this->Db->execute($req);
        $this->normalizeDefaultUploadOrdering();
        $this->Entity->touch();
        new Changelog($this->Entity)->create(new ContentParams('upload_groups', Action::Destroy->value));
        return $result;
    }

    /**
     * Duplicate groups and return a source id => target id map.
     */
    public function duplicate(AbstractEntity $targetEntity): array
    {
        $groups = $this->readAll();
        $sql = 'INSERT INTO upload_groups (entity_id, entity_type, title, ordering) VALUES (:entity_id, :entity_type, :title, :ordering)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $targetEntity->id, PDO::PARAM_INT);
        $req->bindValue(':entity_type', $targetEntity->entityType->toInt(), PDO::PARAM_INT);
        $map = array();
        foreach ($groups as $group) {
            $req->bindValue(':title', $group['title']);
            $req->bindValue(':ordering', $group['ordering'], PDO::PARAM_INT);
            $this->Db->execute($req);
            $map[(int) $group['id']] = $this->Db->lastInsertId();
        }
        return $map;
    }

    private function updateOrdering(array $ordering): void
    {
        $groups = array_column($this->readAll(), null, 'id');
        $sql = 'UPDATE upload_groups SET ordering = :ordering WHERE id = :id AND entity_id = :entity_id AND entity_type = :entity_type';
        $req = $this->Db->prepare($sql);
        foreach ($ordering as $position => $rawId) {
            $id = (int) $rawId;
            if (!array_key_exists($id, $groups)) {
                throw new ImproperActionException('Cannot reorder an upload group that does not belong to this entity.');
            }
            $req->bindValue(':ordering', $position, PDO::PARAM_INT);
            $req->bindValue(':id', $id, PDO::PARAM_INT);
            $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
            $req->bindValue(':entity_type', $this->Entity->entityType->toInt(), PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    /**
     * Preserve the current newest-first flat uploads order when the first group
     * is created, then use explicit ordering from that point on.
     */
    private function initializeDefaultUploadOrdering(): void
    {
        $sql = 'SELECT id FROM uploads
            WHERE item_id = :entity_id AND type = :entity_type AND group_id IS NULL AND state != :state_deleted
            ORDER BY created_at DESC, id DESC';
        $this->renumberDefaultUploads($sql);
    }

    private function normalizeDefaultUploadOrdering(): void
    {
        $sql = 'SELECT id FROM uploads
            WHERE item_id = :entity_id AND type = :entity_type AND group_id IS NULL AND state != :state_deleted
            ORDER BY ordering, id';
        $this->renumberDefaultUploads($sql);
    }

    private function renumberDefaultUploads(string $sql): void
    {
        $selectReq = $this->Db->prepare($sql);
        $selectReq->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $selectReq->bindValue(':entity_type', $this->Entity->entityType->value);
        $selectReq->bindValue(':state_deleted', State::Deleted->value, PDO::PARAM_INT);
        $this->Db->execute($selectReq);

        $updateSql = 'UPDATE uploads SET ordering = :ordering WHERE id = :id AND item_id = :entity_id AND type = :entity_type';
        $updateReq = $this->Db->prepare($updateSql);
        foreach ($selectReq->fetchAll() as $ordering => $upload) {
            $updateReq->bindValue(':ordering', $ordering, PDO::PARAM_INT);
            $updateReq->bindValue(':id', (int) $upload['id'], PDO::PARAM_INT);
            $updateReq->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
            $updateReq->bindValue(':entity_type', $this->Entity->entityType->value);
            $this->Db->execute($updateReq);
        }
    }
}
