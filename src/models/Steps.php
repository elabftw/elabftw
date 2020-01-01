<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\CrudInterface;
use PDO;

/**
 * All about the steps
 */
class Steps implements CrudInterface
{
    /** @var AbstractEntity $Entity instance of Experiments, Templates or Database */
    public $Entity;

    /** @var Db $Db SQL Database */
    protected $Db;

    /**
     * Constructor
     *
     * @param AbstractEntity $entity
     */
    public function __construct(AbstractEntity $entity)
    {
        $this->Db = Db::getConnection();
        $this->Entity = $entity;
    }

    /**
     * Add a step
     *
     * @param string $body the text for the step
     * @return void
     */
    public function create(string $body): void
    {
        $this->Entity->canOrExplode('write');

        // remove any | as they are used in the group_concat
        $body = str_replace('|', ' ', $body);
        $sql = 'INSERT INTO ' . $this->Entity->type . '_steps (item_id, body) VALUES(:item_id, :body)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':body', $body);
        $this->Db->execute($req);
    }

    /**
     * Import a step from a complete step array
     * Used when importing from zip archive (json)
     *
     * @param array $step
     * @return void
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

    /**
     * Toggle the finished column of a step
     *
     * @param int $stepid
     * @return void
     */
    public function finish(int $stepid): void
    {
        $this->Entity->canOrExplode('write');

        $sql = 'UPDATE ' . $this->Entity->type . '_steps SET finished = !finished,
            finished_time = NOW()
            WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $stepid, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Get steps for an entity
     *
     * @return array
     */
    public function readAll(): array
    {
        $sql = 'SELECT * FROM ' . $this->Entity->type . '_steps WHERE item_id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Get steps from an id
     *
     * @param int $id
     * @return array
     */
    public function readFromId(int $id): array
    {
        $sql = 'SELECT * FROM ' . $this->Entity->type . '_steps WHERE item_id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Copy the steps from one entity to an other
     *
     * @param int $id The id of the original entity
     * @param int $newId The id of the new entity that will receive the steps
     * @param bool $fromTpl do we duplicate from template?
     * @return void
     */
    public function duplicate(int $id, int $newId, $fromTpl = false): void
    {
        $table = $this->Entity->type;
        if ($fromTpl) {
            $table = 'experiments_templates';
        }
        $stepsql = 'SELECT body FROM ' . $table . '_steps WHERE item_id = :id';
        $stepreq = $this->Db->prepare($stepsql);
        $stepreq->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($stepreq);

        while ($steps = $stepreq->fetch()) {
            $sql = 'INSERT INTO ' . $this->Entity->type . '_steps (item_id, body) VALUES(:item_id, :body)';
            $req = $this->Db->prepare($sql);
            $this->Db->execute($req, array(
                'item_id' => $newId,
                'body' => $steps['body'],
            ));
        }
    }

    /**
     * Delete a step
     *
     * @param int $id ID of the step
     * @return void
     */
    public function destroy(int $id): void
    {
        $this->Entity->canOrExplode('write');

        $sql = 'DELETE FROM ' . $this->Entity->type . '_steps WHERE id= :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}
