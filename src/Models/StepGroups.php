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
use function sprintf;
use function is_array;

/**
 * Groups used to organize entity steps.
 */
final class StepGroups extends AbstractRest
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
        return sprintf('%s%d/step_groups/', $this->Entity->getApiPath(), $this->Entity->id ?? 0);
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = sprintf('SELECT * FROM %s WHERE item_id = :item_id ORDER BY ordering, id', $this->getTable());
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    #[Override]
    public function readOne(): array
    {
        $sql = sprintf('SELECT * FROM %s WHERE id = :id AND item_id = :item_id', $this->getTable());
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        if ($action !== Action::Create) {
            throw new ImproperActionException('Invalid action for step groups.');
        }
        $this->Entity->canOrExplode(AccessType::Write);
        $title = Filter::title((string) ($reqBody['title'] ?? ''));
        $ordering = count($this->readAll()) + 1;
        $sql = sprintf('INSERT INTO %s (item_id, title, ordering) VALUES (:item_id, :title, :ordering)', $this->getTable());
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':title', $title);
        $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
        $this->Db->execute($req);
        // Keep this id before touch() and the changelog create other rows,
        // otherwise lastInsertId() would no longer point to the new step group
        $id = $this->Db->lastInsertId();
        $this->Entity->touch();
        new Changelog($this->Entity)->create(
            new ContentParams('step_groups', Action::Create->value)
        );
        return $id;
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        if ($action !== Action::Update) {
            throw new ImproperActionException('Invalid action for step groups.');
        }
        $this->Entity->canOrExplode(AccessType::Write);
        // PATCH without a group id targets the collection itself and is used
        // to save the order of groups after drag and drop
        if ($this->id === null) {
            if (!array_key_exists('ordering', $params) || !is_array($params['ordering'])) {
                throw new ImproperActionException('Invalid step group ordering.');
            }
            $this->updateOrdering($params['ordering']);
            $this->Entity->touch();
            new Changelog($this->Entity)->create(new ContentParams('step_groups', Action::Update->value));
            return $this->readAll();
        }

        if (!array_key_exists('title', $params)) {
            throw new ImproperActionException('Invalid parameter for step groups.');
        }
        $title = Filter::title((string) $params['title']);
        $sql = sprintf('UPDATE %s SET title = :title WHERE id = :id AND item_id = :item_id', $this->getTable());
        $req = $this->Db->prepare($sql);
        $req->bindValue(':title', $title);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $this->Entity->touch();
        new Changelog($this->Entity)->create(new ContentParams('step_groups', Action::Update->value));
        return $this->readOne();
    }

    #[Override]
    public function destroy(bool $recursive = false): bool
    {
        $this->Entity->canOrExplode(AccessType::Write);
        $this->readOne();

        // Deleting a group must keep its steps. Move them back to Default group
        // before removing the group itself.
        $stepSql = sprintf('UPDATE %s_steps SET group_id = NULL WHERE item_id = :item_id AND group_id = :group_id', $this->Entity->entityType->value);
        $stepReq = $this->Db->prepare($stepSql);
        $stepReq->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $stepReq->bindParam(':group_id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($stepReq);

        $sql = sprintf('DELETE FROM %s WHERE id = :id AND item_id = :item_id', $this->getTable());
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $result = $this->Db->execute($req);
        $this->normalizeUngroupedStepOrdering();
        $this->Entity->touch();
        new Changelog($this->Entity)->create(new ContentParams('step_groups', Action::Destroy->value));
        return $result;
    }

    /**
     * Duplicate groups and return a source id => target id map.
     * Groups get new database ids when copied. Steps::duplicate() uses this map
     * to reconnect each copied step to the matching copied group
     */
    public function duplicate(AbstractEntity $targetEntity, int $sourceId, int $targetId): array
    {
        $sql = sprintf('SELECT id, title, ordering FROM %s WHERE item_id = :item_id ORDER BY ordering, id', $this->getTable());
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $sourceId, PDO::PARAM_INT);
        $this->Db->execute($req);

        $insert = sprintf('INSERT INTO %s (item_id, title, ordering) VALUES (:item_id, :title, :ordering)', $this->getTable($targetEntity));
        $insertReq = $this->Db->prepare($insert);
        $insertReq->bindParam(':item_id', $targetId, PDO::PARAM_INT);
        $map = array();
        while ($group = $req->fetch()) {
            $insertReq->bindParam(':title', $group['title']);
            $insertReq->bindParam(':ordering', $group['ordering'], PDO::PARAM_INT);
            $this->Db->execute($insertReq);
            $map[(int) $group['id']] = $this->Db->lastInsertId();
        }
        return $map;
    }

    private function updateOrdering(array $ordering): void
    {
        $groups = array_column($this->readAll(), null, 'id');
        $sql = sprintf('UPDATE %s SET ordering = :ordering WHERE id = :id AND item_id = :item_id', $this->getTable());
        $req = $this->Db->prepare($sql);
        foreach ($ordering as $position => $rawId) {
            $id = (int) $rawId;
            if (!array_key_exists($id, $groups)) {
                throw new ImproperActionException('Cannot reorder a step group that does not belong to this entity.');
            }
            $req->bindValue(':ordering', $position, PDO::PARAM_INT);
            $req->bindValue(':id', $id, PDO::PARAM_INT);
            $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    /**
     * Renumber Default group after a group is removed
     * Steps moved out of the deleted group can bring ordering values that
     * overlap with steps already in Default group
     */
    private function normalizeUngroupedStepOrdering(): void
    {
        $stepTable = $this->Entity->entityType->value . '_steps';
        $select = sprintf('SELECT id FROM %s WHERE item_id = :item_id AND group_id IS NULL ORDER BY ordering, id', $stepTable);
        $selectReq = $this->Db->prepare($select);
        $selectReq->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($selectReq);

        $update = sprintf('UPDATE %s SET ordering = :ordering WHERE id = :id AND item_id = :item_id', $stepTable);
        $updateReq = $this->Db->prepare($update);
        foreach ($selectReq->fetchAll() as $ordering => $step) {
            $updateReq->bindValue(':ordering', $ordering, PDO::PARAM_INT);
            $updateReq->bindValue(':id', (int) $step['id'], PDO::PARAM_INT);
            $updateReq->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
            $this->Db->execute($updateReq);
        }
    }

    private function getTable(?AbstractEntity $Entity = null): string
    {
        return ($Entity ?? $this->Entity)->entityType->value . '_step_groups';
    }
}
