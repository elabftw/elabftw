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
        $this->Entity->canOrExplode('write');

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
    public function importFromHowToStep(array $step): void
    {
        $stepArr = array();
        $stepArr['body'] = $step['itemListElement'][0]['text'];
        $stepArr['finished'] = $step['creativeWorkStatus'] === 'finished' ? 1 : 0;
        $stepArr['finished_time'] = $step['temporal'] ?? null;
        $stepArr['ordering'] = $step['position'] ?? null;
        $this->import($stepArr);
    }

    public function readAll(): array
    {
        $sql = 'SELECT * FROM ' . $this->Entity->entityType->value . '_steps WHERE item_id = :id ORDER BY ordering';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readOne(): array
    {
        $sql = 'SELECT * FROM ' . $this->Entity->entityType->value . '_steps WHERE id = :id';
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
        $table = $this->Entity->entityType->value;
        if ($fromTpl) {
            $table = $this->Entity instanceof Templates ? 'experiments_templates' : 'items_types';
        }
        $stepsql = 'SELECT body, ordering FROM ' . $table . '_steps WHERE item_id = :id';
        $stepreq = $this->Db->prepare($stepsql);
        $stepreq->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($stepreq);

        $sql = 'INSERT INTO ' . $this->Entity->entityType->value . '_steps (item_id, body, ordering) VALUES(:item_id, :body, :ordering)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $newId, PDO::PARAM_INT);
        while ($steps = $stepreq->fetch()) {
            $req->bindParam(':body', $steps['body']);
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
            Action::NotifDestroy => $this->destroyNotif(),
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

        $this->getStepDeadline()->destroy();

        $sql = 'DELETE FROM ' . $this->Entity->entityType->value . '_steps WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
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

    private function create(string $body): int
    {
        $body = Filter::title($body);
        // make sure the newly added step is at the bottom
        // count the number of steps and add 1 to be sure we're last
        $ordering = count($this->readAll()) + 1;

        $sql = 'INSERT INTO ' . $this->Entity->entityType->value . '_steps (item_id, body, ordering) VALUES(:item_id, :body, :ordering)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':body', $body);
        $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
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
        $this->getStepDeadline($this->readOne()['deadline'])
            ->create($this->Entity->Users->userData['userid']);

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
            $this->id,
            $this->Entity->id,
            $this->Entity->entityType->toPage(),
            $deadline,
        );
    }
}
