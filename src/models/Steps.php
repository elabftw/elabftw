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
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * All about the steps
 */
class Steps implements CrudInterface
{
    use SortableTrait;

    protected Db $Db;

    public function __construct(public AbstractEntity $Entity, private ?int $id = null)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Add a step
     *
     */
    public function create(ContentParamsInterface $params): int
    {
        $this->Entity->canOrExplode('write');
        // make sure the newly added step is at the bottom
        // count the number of steps and add 1 to be sure we're last
        $ordering = count($this->read(new ContentParams())) + 1;

        $sql = 'INSERT INTO ' . $this->Entity->type . '_steps (item_id, body, ordering) VALUES(:item_id, :content, :ordering)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
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

    public function read(ContentParamsInterface $params): array
    {
        if ($params->getTarget() === 'all') {
            return $this->readAll();
        }
        $this->Entity->canOrExplode('read');

        $sql = 'SELECT * FROM ' . $this->Entity->type . '_steps WHERE item_id = :id ORDER BY ordering';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetchAll($req);
    }

    /**
     * Get the current unfinished steps from experiments owned by current user
     */
    public function readAll(): array
    {
        $sql = "SELECT experiments.id, experiments.title, stepst.finished, stepst.steps_body, stepst.steps_id
            FROM experiments
            CROSS JOIN (
                SELECT item_id, finished,
                GROUP_CONCAT(experiments_steps.body ORDER BY experiments_steps.ordering SEPARATOR '|') AS steps_body,
                GROUP_CONCAT(experiments_steps.id ORDER BY experiments_steps.ordering SEPARATOR '|') AS steps_id
                FROM experiments_steps
                WHERE finished = 0 GROUP BY item_id) AS stepst ON (stepst.item_id = experiments.id)
            WHERE userid = :userid GROUP BY experiments.id ORDER BY experiments.id DESC";

        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $this->Db->fetchAll($req);

        // clean up the results so we get a nice array with experiment id/title and steps with their id/body
        // use reference to edit in place
        foreach ($res as &$exp) {
            $stepIDs = explode('|', $exp['steps_id']);
            $stepsBodies = explode('|', $exp['steps_body']);

            $expSteps = array();
            foreach ($stepIDs as $key => $stepID) {
                $expSteps[] = array($stepID, $stepsBodies[$key]);
            }
            $exp['steps'] = $expSteps;
            unset($exp['steps_body'], $exp['steps_id'], $exp['finished']);
        }

        return $res;
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
            $table = 'experiments_templates';
        }
        $stepsql = 'SELECT body, ordering FROM ' . $table . '_steps WHERE item_id = :id';
        $stepreq = $this->Db->prepare($stepsql);
        $stepreq->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($stepreq);

        while ($steps = $stepreq->fetch()) {
            $sql = 'INSERT INTO ' . $this->Entity->type . '_steps (item_id, body, ordering) VALUES(:item_id, :body, :ordering)';
            $req = $this->Db->prepare($sql);
            $this->Db->execute($req, array(
                'item_id' => $newId,
                'body' => $steps['body'],
                'ordering' => $steps['ordering'],
            ));
        }
    }

    public function update(ContentParamsInterface $params): bool
    {
        $this->Entity->canOrExplode('write');
        if ($params->getTarget() === 'body') {
            return $this->updateBody($params->getContent());
        }
        if ($params->getTarget() === 'finished') {
            return $this->toggleFinished();
        }
        return false;
    }

    public function destroy(): bool
    {
        $this->Entity->canOrExplode('write');

        $sql = 'DELETE FROM ' . $this->Entity->type . '_steps WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function toggleFinished(): bool
    {
        $sql = 'UPDATE ' . $this->Entity->type . '_steps SET finished = !finished,
            finished_time = NOW() WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function updateBody(string $content): bool
    {
        $sql = 'UPDATE ' . $this->Entity->type . '_steps SET body = :content WHERE id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':content', $content, PDO::PARAM_STR);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
