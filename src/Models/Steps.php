<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Notifications\StepDeadline;
use Elabftw\Params\ContentParams;
use Elabftw\Params\OrderingParams;
use Elabftw\Params\StepParams;
use Elabftw\Services\Filter;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

use function array_column;
use function array_intersect;
use function array_keys;
use function _;
use function array_key_exists;
use function count;
use function in_array;
use function sprintf;
use function str_replace;
use function is_array;

/**
 * All about the steps
 */
final class Steps extends AbstractRest
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
        return sprintf('%s%d/steps/', $this->Entity->getApiPath(), $this->Entity->id ?? 0);
    }

    /**
     * Import a step from a complete step array
     * Used when importing from zip archive (json)
     *
     * @param array<string, mixed> $step
     */
    public function import(array $step): void
    {
        $this->Entity->canOrExplode(AccessType::Write);

        $body = str_replace('|', ' ', $step['body']);
        $sql = 'INSERT INTO ' . $this->Entity->entityType->value . '_steps (item_id, body, ordering, finished, finished_time)
            VALUES(:item_id, :body, :ordering, :finished, :finished_time)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':body', $body);
        $req->bindParam(':ordering', $step['ordering'], PDO::PARAM_INT);
        $req->bindParam(':finished', $step['finished'], PDO::PARAM_INT);
        $req->bindParam(':finished_time', $step['finished_time']);
        $this->Db->execute($req);
    }

    /**
     * Create a step from https://schema.org/HowToStep
     * Example:
     * {
     *  "@id": "howtostep://bde30f48-b16c-4050-ba63-0c34d1bafb7d",
     *  "@type": "HowToStep",
     *  "position": 2,
     *  "creativeWorkStatus": "unfinished",
     *  "itemListElement": {
     *    "@id": "howtodirection://7c55611b-3400-48fa-bcc3-db05925b5ab8"
     *  }
     * },
     */
    public function importFromHowToStep(array $howToStep, string $body): void
    {
        $this->import(array(
            'body' => $body,
            'finished' => $howToStep['creativeWorkStatus'] === 'finished' ? 1 : 0,
            'finished_time' => $howToStep['temporal'] ?? null,
            'ordering' => $howToStep['position'] ?? null,
        ));
    }

    /**
     * Create a step from unflattened step
     * Example:
     *   "@type": "HowToStep",
     *   "position": 4,
     *   "creativeWorkStatus": "finished",
     *   "expires": "2024-05-19T04:24:54+02:00",
     *   "temporal": "2024-05-19T03:24:54+02:00",
     *   "itemListElement": {
     *     "@type":"HowToDirection",
     *     "text": "finished with deadline"
     *   }
     */
    public function importFromHowToStepOld(array $step): void
    {
        $stepArr = array();
        $stepArr['body'] = $step['itemListElement'][0]['text'];
        $stepArr['finished'] = $step['creativeWorkStatus'] === 'finished' ? 1 : 0;
        $stepArr['finished_time'] = $step['temporal'] ?? null;
        $stepArr['ordering'] = $step['position'] ?? null;
        $this->import($stepArr);
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $stepTable = $this->Entity->entityType->value . '_steps';
        $groupTable = $this->Entity->entityType->value . '_step_groups';
        // Step ordering is local to each group. Named groups follow their group
        // ordering, while Default group (group_id = NULL) are displayed last
        $sql = sprintf(
            'SELECT st.* FROM %s AS st
                LEFT JOIN %s AS sg ON sg.id = st.group_id AND sg.item_id = st.item_id
                WHERE st.item_id = :id
                ORDER BY (st.group_id IS NULL) ASC, sg.ordering ASC, sg.id ASC, st.ordering ASC, st.id ASC',
            $stepTable,
            $groupTable,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    #[Override]
    public function readOne(): array
    {
        $sql = 'SELECT * FROM ' . $this->Entity->entityType->value . '_steps WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    // Copy Steps from one entity to another
    public function duplicate(AbstractEntity $targetEntity, int $id, int $newId): void
    {
        $sourceTable = $this->Entity->entityType->value;
        $targetTable = $targetEntity->entityType->value;
        // Copy groups first because copied steps must reference the new group ids,
        // not the ids belonging to the source entity
        $groupMap = new StepGroups($this->Entity)->duplicate($targetEntity, $id, $newId);
        $stepsql = sprintf('SELECT body, ordering, is_immutable, group_id FROM %s_steps WHERE item_id = :id', $sourceTable);
        $stepreq = $this->Db->prepare($stepsql);
        $stepreq->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($stepreq);

        $sql = sprintf('INSERT INTO %s_steps (item_id, body, ordering, is_immutable, group_id) VALUES (:item_id, :body, :ordering, :is_immutable, :group_id)', $targetTable);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $newId, PDO::PARAM_INT);
        while ($step = $stepreq->fetch()) {
            $groupId = $step['group_id'] === null ? null : ($groupMap[(int) $step['group_id']] ?? null);
            $req->bindParam(':body', $step['body']);
            $req->bindParam(':ordering', $step['ordering'], PDO::PARAM_INT);
            $req->bindParam(':is_immutable', $step['is_immutable'], PDO::PARAM_INT);
            $req->bindValue(':group_id', $groupId, $groupId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    /**
     * Reorder steps belonging to the parent entity.
     */
    public function updateOrdering(OrderingParams $params): void
    {
        $this->Entity->canOrExplode(AccessType::Write);

        $table = $this->Entity->entityType->value . '_steps';
        if ($params->table->value !== $table) {
            throw new ImproperActionException(_('The steps table does not match the entity type.'));
        }

        $steps = array_column($this->readAll(), null, 'id');
        $enforceImmutability = in_array(
            $this->Entity->entityType,
            array(EntityType::Experiments, EntityType::Items),
            true,
        );
        foreach ($params->ordering as $id) {
            if (!array_key_exists($id, $steps)) {
                throw new ImproperActionException(_('Cannot reorder a step that does not belong to this entity.'));
            }
            if ($enforceImmutability && (int) $steps[$id]['is_immutable'] === 1) {
                throw new ImproperActionException(_('This step is immutable: it cannot be modified.'));
            }
        }

        $sql = sprintf(
            'UPDATE %s_steps SET ordering = :ordering WHERE id = :id AND item_id = :item_id',
            $this->Entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        foreach ($params->ordering as $ordering => $id) {
            $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
            $req->bindParam(':id', $id, PDO::PARAM_INT);
            $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
            $this->Db->execute($req);
        }

        $this->Entity->touch();
        $Changelog = new Changelog($this->Entity);
        $Changelog->create(new ContentParams('steps', Action::Update->value));
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $this->Entity->canOrExplode(AccessType::Write);
        $this->Entity->touch();
        // Connected sortables update the whole step layout at once, so this
        // collection-level PATCH does not target one specific step id.
        if ($action === Action::Update && $this->id === null && array_key_exists('grouped_ordering', $params)) {
            if (!is_array($params['grouped_ordering'])) {
                throw new ImproperActionException(_('Invalid grouped steps ordering.'));
            }
            $this->updateGroupedOrdering($params['grouped_ordering']);
            $Changelog = new Changelog($this->Entity);
            $Changelog->create(new ContentParams('steps', Action::Update->value));
            return $this->readAll();
        }
        if ($action === Action::Update && $this->id === null) {
            throw new ImproperActionException(_('A step id is required for this update.'));
        }
        match ($action) {
            Action::Finish => $this->toggleFinished(),
            Action::Notif => $this->toggleNotif(),
            Action::NotifDestroy => $this->destroyNotif(),
            Action::Update => (
                function () use ($params) {
                    // prevent updates to protected fields on immutable steps
                    $protected = array('body', 'ordering', 'is_immutable', 'group_id');
                    $enforceImmutability = in_array($this->Entity->entityType->value, array('experiments', 'items'), true);
                    // if we're on experiments/items, prevent any change to is_immutable. It is only allowed on templates
                    if ($enforceImmutability && array_key_exists('is_immutable', $params)) {
                        throw new ImproperActionException(_('The immutability parameter cannot be modified from experiments or resources.'));
                    }
                    if ($enforceImmutability && $this->readOne()['is_immutable'] === 1
                        && count(array_intersect(array_keys($params), $protected)) > 0) {
                        throw new ImproperActionException(_('This step is immutable: it cannot be modified.'));
                    }
                    foreach ($params as $key => $value) {
                        if ($key === 'group_id') {
                            $this->updateGroupId($value);
                            continue;
                        }
                        // value can be null with deadline removal
                        $this->update(new StepParams($key, $value ?? ''));
                    }
                }
            )(),
            Action::ForceLock => $this->setImmutable(1),
            Action::ForceUnlock => $this->setImmutable(0),
            default => throw new ImproperActionException('Invalid action for steps.'),
        };
        $Changelog = new Changelog($this->Entity);
        $Changelog->create(new ContentParams('steps', $action->value));
        if ($this->id) {
            return $this->readOne();
        }
        return $this->readAll();
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $this->Entity->canOrExplode(AccessType::Write);
        $this->Entity->touch();
        $Changelog = new Changelog($this->Entity);
        $Changelog->create(new ContentParams('steps', $action->value));
        $groupId = ($reqBody['group_id'] ?? null) === null || ($reqBody['group_id'] ?? '') === ''
            ? null
            : (int) $reqBody['group_id'];
        return $this->create($reqBody['body'] ?? 'RTFM', $groupId);
    }

    #[Override]
    public function destroy(bool $recursive = false): bool
    {
        $this->Entity->canOrExplode(AccessType::Write);
        $this->Entity->touch();
        $Changelog = new Changelog($this->Entity);
        /** @psalm-suppress PossiblyNullArgument */
        $Changelog->create(new ContentParams('steps', sprintf('Removed step with id: %d', $this->id)));

        $this->getStepDeadline()->destroy();

        $sql = 'DELETE FROM ' . $this->Entity->entityType->value . '_steps WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function setImmutable(int $value): bool
    {
        $sql = sprintf(
            'UPDATE %s_steps SET is_immutable = :content WHERE item_id = :item_id',
            $this->Entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $value, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function update(StepParams $params): bool
    {
        $sql = sprintf(
            'UPDATE %s_steps SET %s = :content WHERE id = :id AND item_id = :item_id',
            $this->Entity->entityType->value,
            $params->getColumn(),
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function create(string $body, ?int $groupId = null): int
    {
        $body = Filter::title($body);
        $this->assertGroupBelongsToEntity($groupId);
        $ordering = $this->getNextOrdering($groupId);

        $sql = 'INSERT INTO ' . $this->Entity->entityType->value . '_steps (item_id, group_id, body, ordering) VALUES(:item_id, :group_id, :body, :ordering)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':group_id', $groupId, $groupId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $req->bindValue(':body', $body);
        $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Move one step to another group, or back to Default group
     * A direct group change appends the step to the end of its new group
     */
    private function updateGroupId(mixed $value): bool
    {
        $groupId = $value === null || $value === '' ? null : (int) $value;
        $this->assertGroupBelongsToEntity($groupId);
        $ordering = $this->getNextOrdering($groupId);
        $sql = sprintf(
            'UPDATE %s_steps SET group_id = :group_id, ordering = :ordering WHERE id = :id AND item_id = :item_id',
            $this->Entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':group_id', $groupId, $groupId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

     // Apply the complete group/step layout sent after drag and drop
    private function updateGroupedOrdering(array $groups): void
    {
        $steps = array_column($this->readAll(), null, 'id');
        // Keep the original position of every step
        // Immutable steps may be part of the payload, but they are only rejected when they actually moved
        $positions = array();
        $nextPosition = array();
        foreach ($steps as $step) {
            $key = $step['group_id'] === null ? 'null' : (string) $step['group_id'];
            $positions[(int) $step['id']] = $nextPosition[$key] ?? 0;
            $nextPosition[$key] = ($nextPosition[$key] ?? 0) + 1;
        }

        $enforceImmutability = in_array(
            $this->Entity->entityType,
            array(EntityType::Experiments, EntityType::Items),
            true,
        );
        $seen = array();
        $sql = sprintf(
            'UPDATE %s_steps SET group_id = :group_id, ordering = :ordering WHERE id = :id AND item_id = :item_id',
            $this->Entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);

        foreach ($groups as $group) {
            if (!is_array($group) || !array_key_exists('step_ids', $group) || !is_array($group['step_ids'])) {
                throw new ImproperActionException(_('Invalid grouped steps ordering.'));
            }
            $rawGroupId = $group['group_id'] ?? null;
            $groupId = $rawGroupId === null || $rawGroupId === '' ? null : (int) $rawGroupId;
            $this->assertGroupBelongsToEntity($groupId);

            foreach ($group['step_ids'] as $ordering => $rawStepId) {
                $stepId = (int) $rawStepId;
                if (!array_key_exists($stepId, $steps) || isset($seen[$stepId])) {
                    throw new ImproperActionException(_('Cannot reorder a step that does not belong to this entity.'));
                }
                $seen[$stepId] = true;
                $currentGroupId = $steps[$stepId]['group_id'] === null ? null : (int) $steps[$stepId]['group_id'];
                if ($enforceImmutability && (int) $steps[$stepId]['is_immutable'] === 1
                    && ($currentGroupId !== $groupId || $positions[$stepId] !== $ordering)) {
                    throw new ImproperActionException(_('This step is immutable: it cannot be modified.'));
                }
                $req->bindValue(':group_id', $groupId, $groupId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $req->bindValue(':ordering', $ordering, PDO::PARAM_INT);
                $req->bindValue(':id', $stepId, PDO::PARAM_INT);
                $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
                $this->Db->execute($req);
            }
        }
    }

    // Make sure a group belongs to the same parent entity as the step
    private function assertGroupBelongsToEntity(?int $groupId): void
    {
        if ($groupId === null) {
            return;
        }
        if ($groupId < 1) {
            throw new ImproperActionException(_('Invalid step group.'));
        }
        // The foreign key only proves that the group exists
        // readOne() also checks item_id, preventing a step from using another entity's group
        new StepGroups($this->Entity, $groupId)->readOne();
    }

    // Return the next position inside one group. NULL means Default group
    private function getNextOrdering(?int $groupId): int
    {
        // MySQL's <=> is null-safe, so this query works for both a real group
        // id and Default group where group_id is NULL
        $sql = sprintf(
            'SELECT COALESCE(MAX(ordering), -1) + 1 AS next_ordering FROM %s_steps WHERE item_id = :item_id AND group_id <=> :group_id',
            $this->Entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':group_id', $groupId, $groupId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    private function toggleFinished(): bool
    {
        $sql = sprintf(
            'UPDATE %s_steps
                SET finished = !finished,
                    finished_time = NOW(),
                    deadline = null,
                    deadline_notif = 0
                WHERE id = :id
                    AND item_id = :item_id',
            $this->Entity->entityType->value
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $res = $this->Db->execute($req);

        // delete potential notification if step is finished
        if ($this->readOne()['finished'] === 1) {
            $this->getStepDeadline()->destroy();
        }

        return $res;
    }

    private function toggleNotif(): bool
    {
        $this->getStepDeadline($this->readOne()['deadline'])->create();

        return $this->setDeadlineNotif('!deadline_notif');
    }

    private function destroyNotif(): bool
    {
        $this->getStepDeadline()->destroy();

        return $this->setDeadlineNotif('0');
    }

    /**
     * set the deadline_notif column so we know whether this step has a notif set for the deadline
     */
    private function setDeadlineNotif(string $value): bool
    {
        $sql = sprintf(
            'UPDATE %s_steps SET deadline_notif = %s WHERE id = :id AND item_id = :item_id',
            $this->Entity->entityType->value,
            $value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function getStepDeadline(string $deadline = ''): StepDeadline
    {
        /** @psalm-suppress PossiblyNullArgument */
        return new StepDeadline(
            $this->Entity->Users,
            $this->id,
            $this->Entity->id,
            $this->Entity->entityType->toPage(),
            $deadline,
        );
    }
}
