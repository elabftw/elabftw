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
use PDOStatement;
use Throwable;

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
    public function import(array $step, ?int $groupId = null): void
    {
        $this->Entity->canOrExplode(AccessType::Write);
        $this->assertGroupBelongsToEntity($groupId);

        $body = str_replace('|', ' ', $step['body']);
        $sql = sprintf(
            'INSERT INTO %s (item_id, group_id, body, ordering, finished, finished_time)
                VALUES(:item_id, :group_id, :body, :ordering, :finished, :finished_time)',
            $this->getTable(),
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->bindNullableInt($req, ':group_id', $groupId);
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
    public function importFromHowToStep(array $howToStep, string $body, ?int $groupId = null): void
    {
        $this->import(array(
            'body' => $body,
            'finished' => $howToStep['creativeWorkStatus'] === 'finished' ? 1 : 0,
            'finished_time' => $howToStep['temporal'] ?? null,
            'ordering' => $howToStep['position'] ?? null,
        ), $groupId);
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
        $this->importFromHowToStep($step, $step['itemListElement'][0]['text']);
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        // Step ordering is local to each group. Default group (group_id = NULL)
        // is displayed first, followed by named groups using their group ordering
        $sql = sprintf(
            'SELECT st.* FROM %s AS st
                LEFT JOIN %s_step_groups AS sg ON sg.id = st.group_id AND sg.entity_id = st.item_id
                WHERE st.item_id = :id
                ORDER BY (st.group_id IS NOT NULL) ASC, sg.ordering ASC, sg.id ASC, st.ordering ASC, st.id ASC',
            $this->getTable(),
            $this->Entity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    #[Override]
    public function readOne(): array
    {
        $req = $this->Db->prepare(sprintf('SELECT * FROM %s WHERE id = :id AND item_id = :item_id', $this->getTable()));
        $this->bindStepIds($req);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    /**
     * Copy Steps from one entity to another
     *
     * @return array<int, int> map of source step id to new step id
     */
    public function duplicate(AbstractEntity $targetEntity, int $id, int $newId): array
    {
        // Copy groups first because copied steps must reference the new group ids,
        // not the ids belonging to the source entity
        $groupMap = new StepGroups($this->Entity)->duplicate($targetEntity, $id, $newId);
        $stepsql = sprintf('SELECT id, body, ordering, is_immutable, group_id FROM %s WHERE item_id = :id', $this->getTable());
        $stepreq = $this->Db->prepare($stepsql);
        $stepreq->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($stepreq);

        $sql = sprintf(
            'INSERT INTO %s_steps (item_id, body, ordering, is_immutable, group_id) VALUES (:item_id, :body, :ordering, :is_immutable, :group_id)',
            $targetEntity->entityType->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $newId, PDO::PARAM_INT);
        $stepsMap = array();
        while ($step = $stepreq->fetch()) {
            $groupId = $step['group_id'] === null ? null : ($groupMap[(int) $step['group_id']] ?? null);
            $req->bindParam(':body', $step['body']);
            $req->bindParam(':ordering', $step['ordering'], PDO::PARAM_INT);
            $req->bindParam(':is_immutable', $step['is_immutable'], PDO::PARAM_INT);
            $this->Db->bindNullableInt($req, ':group_id', $groupId);
            $this->Db->execute($req);
            $stepsMap[(int) $step['id']] = $this->Db->lastInsertId();
        }
        return $stepsMap;
    }

    /**
     * Reorder steps belonging to the parent entity.
     */
    public function updateOrdering(OrderingParams $params): void
    {
        $this->Entity->canOrExplode(AccessType::Write);

        if ($params->table->value !== $this->getTable()) {
            throw new ImproperActionException(_('The steps table does not match the entity type.'));
        }

        $steps = array_column($this->readAll(), null, 'id');
        $enforceImmutability = $this->enforcesImmutability();
        foreach ($params->ordering as $id) {
            if (!array_key_exists($id, $steps)) {
                throw new ImproperActionException(_('Cannot reorder a step that does not belong to this entity.'));
            }
            if ($enforceImmutability && (int) $steps[$id]['is_immutable'] === 1) {
                throw new ImproperActionException(_('This step is immutable: it cannot be modified.'));
            }
        }

        $req = $this->Db->prepare(sprintf('UPDATE %s SET ordering = :ordering WHERE id = :id AND item_id = :item_id', $this->getTable()));
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        foreach ($params->ordering as $ordering => $id) {
            $req->bindValue(':ordering', $ordering, PDO::PARAM_INT);
            $req->bindValue(':id', $id, PDO::PARAM_INT);
            $this->Db->execute($req);
        }

        $this->Entity->touch();
        $this->logChange('Reordered steps');
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
            $this->logChange('Reordered steps');
            return $this->readAll();
        }
        if ($action === Action::Update && $this->id === null) {
            throw new ImproperActionException(_('A step id is required for this update.'));
        }
        match ($action) {
            Action::Finish => $this->toggleFinished(),
            Action::Notif => $this->toggleNotif(),
            Action::NotifDestroy => $this->destroyNotif(),
            Action::Update => $this->updateFields($params),
            Action::ForceLock => $this->setImmutable(1),
            Action::ForceUnlock => $this->setImmutable(0),
            default => throw new ImproperActionException('Invalid action for steps.'),
        };
        $message = $action->value;
        if ($this->id !== null) {
            if ($action === Action::Finish) {
                $finished = (int) $this->readOne()['finished'] === 1;
                $message = sprintf('%s step with id: %d', $finished ? 'Finished' : 'Unfinished', $this->id);
            } elseif ($action === Action::Update) {
                $message = sprintf('Updated step with id: %d', $this->id);
            }
        }
        $this->logChange($message);
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
        $id = $this->create($reqBody['body'] ?? 'RTFM', $this->toGroupId($reqBody['group_id'] ?? null));
        $this->logChange(sprintf('Created step with id: %d', $id));
        return $id;
    }

    #[Override]
    public function destroy(bool $recursive = false): bool
    {
        $this->Entity->canOrExplode(AccessType::Write);
        $this->Entity->touch();
        /** @psalm-suppress PossiblyNullArgument */
        $this->logChange(sprintf('Deleted step with id: %d', $this->id));

        $this->getStepDeadline()->destroy();

        $req = $this->Db->prepare(sprintf('DELETE FROM %s WHERE id = :id AND item_id = :item_id', $this->getTable()));
        $this->bindStepIds($req);
        return $this->Db->execute($req);
    }

    private function getTable(): string
    {
        return $this->Entity->entityType->value . '_steps';
    }

    // Bind the step id and its parent entity id, so a query can never reach a step from another entity
    private function bindStepIds(PDOStatement $req): void
    {
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
    }

    // Immutability is set on templates and enforced on the experiments and resources created from them
    private function enforcesImmutability(): bool
    {
        return in_array($this->Entity->entityType, array(EntityType::Experiments, EntityType::Items), true);
    }

    // Group ids arrive from requests as null, '' or a numeric value. NULL means Default group
    private function toGroupId(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function logChange(string $message): void
    {
        new Changelog($this->Entity)->create(new ContentParams('steps', $message));
    }

    private function setImmutable(int $value): bool
    {
        $req = $this->Db->prepare(sprintf('UPDATE %s SET is_immutable = :content WHERE item_id = :item_id', $this->getTable()));
        $req->bindValue(':content', $value, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function updateFields(array $params): bool
    {
        // prevent updates to protected fields on immutable steps
        $protected = array('body', 'ordering', 'is_immutable', 'group_id');
        $enforceImmutability = $this->enforcesImmutability();
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
        return true;
    }

    private function update(StepParams $params): bool
    {
        $sql = sprintf(
            'UPDATE %s SET %s = :content WHERE id = :id AND item_id = :item_id',
            $this->getTable(),
            $params->getColumn(),
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $this->bindStepIds($req);
        $res = $this->Db->execute($req);

        if ($params->getColumn() === 'deadline') {
            // Use the stored deadline so the notification has the same datetime format.
            $step = $this->readOne();
            if ($step['deadline'] === null) {
                $this->destroyNotif();
            } elseif ((int) $step['deadline_notif'] === 1) {
                $this->getStepDeadline($step['deadline'])->updateDeadline();
            }
        }

        return $res;
    }

    private function create(string $body, ?int $groupId = null): int
    {
        $body = Filter::step($body);
        $this->assertGroupBelongsToEntity($groupId);
        $ordering = $this->getNextOrdering($groupId);

        $sql = sprintf('INSERT INTO %s (item_id, group_id, body, ordering) VALUES(:item_id, :group_id, :body, :ordering)', $this->getTable());
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->bindNullableInt($req, ':group_id', $groupId);
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
        $groupId = $this->toGroupId($value);
        $this->assertGroupBelongsToEntity($groupId);
        $ordering = $this->getNextOrdering($groupId);
        $sql = sprintf(
            'UPDATE %s SET group_id = :group_id, ordering = :ordering WHERE id = :id AND item_id = :item_id',
            $this->getTable(),
        );
        $req = $this->Db->prepare($sql);
        $this->Db->bindNullableInt($req, ':group_id', $groupId);
        $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
        $this->bindStepIds($req);
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

        $enforceImmutability = $this->enforcesImmutability();
        $seen = array();
        $sql = sprintf(
            'UPDATE %s SET group_id = :group_id, ordering = :ordering WHERE id = :id AND item_id = :item_id',
            $this->getTable(),
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);

        foreach ($groups as $group) {
            if (!is_array($group) || !array_key_exists('step_ids', $group) || !is_array($group['step_ids'])) {
                throw new ImproperActionException(_('Invalid grouped steps ordering.'));
            }
            $groupId = $this->toGroupId($group['group_id'] ?? null);
            $this->assertGroupBelongsToEntity($groupId);

            foreach ($group['step_ids'] as $ordering => $rawStepId) {
                $stepId = (int) $rawStepId;
                if (!array_key_exists($stepId, $steps) || isset($seen[$stepId])) {
                    throw new ImproperActionException(_('Cannot reorder a step that does not belong to this entity.'));
                }
                $seen[$stepId] = true;
                $currentGroupId = $this->toGroupId($steps[$stepId]['group_id']);
                if ($enforceImmutability && (int) $steps[$stepId]['is_immutable'] === 1
                    && ($currentGroupId !== $groupId || $positions[$stepId] !== $ordering)) {
                    throw new ImproperActionException(_('This step is immutable: it cannot be modified.'));
                }
                $this->Db->bindNullableInt($req, ':group_id', $groupId);
                $req->bindValue(':ordering', $ordering, PDO::PARAM_INT);
                $req->bindValue(':id', $stepId, PDO::PARAM_INT);
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
        // readOne() also checks entity_id, preventing a step from using another entity's group
        new StepGroups($this->Entity, $groupId)->readOne();
    }

    // Return the next position inside one group. NULL means Default group
    private function getNextOrdering(?int $groupId): int
    {
        // MySQL's <=> is null-safe, so this query works for both a real group
        // id and Default group where group_id is NULL
        $sql = sprintf(
            'SELECT COALESCE(MAX(ordering), -1) + 1 AS next_ordering FROM %s WHERE item_id = :item_id AND group_id <=> :group_id',
            $this->getTable(),
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->bindNullableInt($req, ':group_id', $groupId);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    private function toggleFinished(): bool
    {
        $sql = sprintf(
            'UPDATE %s
                SET finished = !finished,
                    finished_time = NOW(),
                    deadline = null,
                    deadline_notif = 0
                WHERE id = :id
                    AND item_id = :item_id',
            $this->getTable(),
        );
        $req = $this->Db->prepare($sql);
        $this->bindStepIds($req);
        $res = $this->Db->execute($req);

        // delete potential notification if step is finished
        if ($this->readOne()['finished'] === 1) {
            $this->getStepDeadline()->destroy();
        }

        return $res;
    }

    private function toggleNotif(): bool
    {
        // serialize concurrent toggles on this step: without the row lock, two requests could both read deadline_notif = 0
        // and the second StepDeadline::create() would delete the notification created by the first one
        $this->Db->beginTransaction();
        try {
            $step = $this->readOneForUpdate();
            // base the toggle on the stored flag so the column and the notifications table cannot drift apart
            $res = $step['deadline_notif'] === 1
                ? $this->destroyNotif()
                : $this->createNotif($step['deadline']);
            $this->Db->commit();
            return $res;
        } catch (Throwable $e) {
            $this->Db->rollBack();
            throw $e;
        }
    }

    /**
     * Read the deadline columns of this step and lock its row until the end of the current transaction
     */
    private function readOneForUpdate(): array
    {
        $sql = sprintf(
            'SELECT deadline, deadline_notif FROM %s WHERE id = :id AND item_id = :item_id FOR UPDATE',
            $this->getTable(),
        );
        $req = $this->Db->prepare($sql);
        $this->bindStepIds($req);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    private function createNotif(?string $deadline): bool
    {
        if ($deadline === null) {
            throw new ImproperActionException(_('A deadline is required to enable a notification for this step.'));
        }
        $StepDeadline = $this->getStepDeadline($deadline);
        // remove any leftover notification first, as StepDeadline::create() would otherwise treat it as a toggle off
        $StepDeadline->destroy();
        $StepDeadline->create();

        return $this->setDeadlineNotif(true);
    }

    private function destroyNotif(): bool
    {
        $this->getStepDeadline()->destroy();

        return $this->setDeadlineNotif(false);
    }

    /**
     * set the deadline_notif column so we know whether this step has a notif set for the deadline
     */
    private function setDeadlineNotif(bool $enabled): bool
    {
        $req = $this->Db->prepare(sprintf('UPDATE %s SET deadline_notif = :value WHERE id = :id AND item_id = :item_id', $this->getTable()));
        $req->bindValue(':value', (int) $enabled, PDO::PARAM_INT);
        $this->bindStepIds($req);
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
