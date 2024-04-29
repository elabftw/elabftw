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

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\StepParams;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Models\Notifications\StepDeadline;
use Elabftw\Services\Filter;
use Elabftw\Traits\SetIdTrait;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * All about the steps
 */
class Steps implements RestInterface
{
    use SortableTrait;
    use SetIdTrait;

    protected Db $Db;

    public function __construct(public AbstractEntity $Entity, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->setId($id);
    }

    public function getPage(): string
    {
        return sprintf('api/v2/%s/%d/steps/', $this->Entity->page, $this->Entity->id ?? 0);
    }

    /**
     * Import a step from a complete step array
     * Used when importing from zip archive (json)
     *
     * @param array<string, mixed> $step
     */
    public function import(array $step): void
    {
        $this->Entity->canOrExplode('write');

        $body = str_replace('|', ' ', $step['body']);
        $sql = 'INSERT INTO ' . $this->Entity->type . '_steps (item_id, body, ordering, finished, finished_time)
            VALUES(:item_id, :body, :ordering, :finished, :finished_time)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':body', $body);
        $req->bindParam(':ordering', $step['ordering']);
        $req->bindParam(':finished', $step['finished']);
        $req->bindParam(':finished_time', $step['finished_time']);
        $this->Db->execute($req);
    }

    public function readAll(): array
    {
        $sql = 'SELECT * FROM ' . $this->Entity->type . '_steps WHERE item_id = :id ORDER BY ordering';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readOne(): array
    {
        $sql = 'SELECT * FROM ' . $this->Entity->type . '_steps WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    /**
     * Copy the steps from one entity to an other
     *
     * @param int $id The id of the original entity
     * @param int $newId The id of the new entity that will receive the steps
     * @param bool $fromTpl do we duplicate from template?
     */
    public function duplicate(int $id, int $newId, $fromTpl = false): void
    {
        $table = $this->Entity->type;
        if ($fromTpl) {
            $table = $this->Entity instanceof Experiments ? 'experiments_templates' : 'items_types';
        }
        $stepsql = 'SELECT body, ordering FROM ' . $table . '_steps WHERE item_id = :id';
        $stepreq = $this->Db->prepare($stepsql);
        $stepreq->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($stepreq);

        $sql = 'INSERT INTO ' . $this->Entity->type . '_steps (item_id, body, ordering) VALUES(:item_id, :body, :ordering)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $newId, PDO::PARAM_INT);
        while ($steps = $stepreq->fetch()) {
            $req->bindParam(':body', $steps['body'], PDO::PARAM_STR);
            $req->bindParam(':ordering', $steps['ordering'], PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    public function patch(Action $action, array $params): array
    {
        $this->Entity->canOrExplode('write');
        $this->Entity->touch();
        match ($action) {
            Action::Finish => $this->toggleFinished(),
            Action::Notif => $this->toggleNotif(),
            Action::Update => (
                function () use ($params) {
                    foreach ($params as $key => $value) {
                        // value can be null with deadline removal
                        $this->update(new StepParams($key, $value ?? ''));
                    }
                }
            )(),
            default => throw new ImproperActionException('Invalid action for steps.'),
        };
        $Changelog = new Changelog($this->Entity);
        $Changelog->create(new ContentParams('steps', $action->value));
        return $this->readOne();
    }

    public function postAction(Action $action, array $reqBody): int
    {
        $this->Entity->canOrExplode('write');
        $this->Entity->touch();
        $Changelog = new Changelog($this->Entity);
        $Changelog->create(new ContentParams('steps', $action->value));
        return $this->create($reqBody['body'] ?? 'RTFM');
    }

    public function destroy(): bool
    {
        $this->Entity->canOrExplode('write');
        $this->Entity->touch();
        $Changelog = new Changelog($this->Entity);
        /** @psalm-suppress PossiblyNullArgument */
        $Changelog->create(new ContentParams('steps', sprintf('Removed step with id: %d', $this->id)));

        $sql = 'DELETE FROM ' . $this->Entity->type . '_steps WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function update(StepParams $params): bool
    {
        $sql = 'UPDATE ' . $this->Entity->type . '_steps SET ' . $params->getColumn() . ' = :content WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent(), PDO::PARAM_STR);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function create(string $body): int
    {
        $body = Filter::title($body);
        // make sure the newly added step is at the bottom
        // count the number of steps and add 1 to be sure we're last
        $ordering = count($this->readAll()) + 1;

        $sql = 'INSERT INTO ' . $this->Entity->type . '_steps (item_id, body, ordering) VALUES(:item_id, :body, :ordering)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':body', $body);
        $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    private function toggleFinished(): bool
    {
        $sql = 'UPDATE ' . $this->Entity->type . '_steps SET finished = !finished,
            finished_time = NOW(), deadline = null, deadline_notif = 0 WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function toggleNotif(): bool
    {
        // get the current deadline value so we can insert it in the notification
        $sql = 'SELECT deadline FROM ' . $this->Entity->type . '_steps WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->execute();
        $step = $req->fetch();

        // now create a notification if none exist for this step id already
        $Notifications = new StepDeadline(
            $this->id,
            $this->Entity->entityData['id'],
            $this->Entity->page,
            $step['deadline'],
        );
        $Notifications->create($this->Entity->Users->userData['userid']);

        // update the deadline_notif column so we now if this step has a notif set for deadline or not
        $sql = 'UPDATE ' . $this->Entity->type . '_steps SET deadline_notif = !deadline_notif WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
