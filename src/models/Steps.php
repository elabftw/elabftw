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
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Interfaces\CrudInterface;
use PDO;

/**
 * All about the experiments steps
 */
class Steps implements CrudInterface
{
    /** @var Db $Db SQL Database */
    protected $Db;

    /** instance of Experiments */
    public $Experiments;

    /**
     * Constructor
     *
     * @param Experiments $experiments
     */
    public function __construct(Experiments $experiments)
    {
        $this->Db = Db::getConnection();
        $this->Experiments = $experiments;
    }

    /**
     * Add a step to an experiment
     *
     * @param string $body the text for the step
     * @return void
     */
    public function create(string $body): void
    {
        $this->Experiments->canOrExplode('write');

        // remove any | as they are used in the group_concat
        $body = str_replace('|', ' ', $body);
        $sql = "INSERT INTO experiments_steps (item_id, body) VALUES(:item_id, :body)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Experiments->id, PDO::PARAM_INT);
        $req->bindParam(':body', $body);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Toggle the finished column of a step
     *
     * @param int $stepid
     * @return void
     */
    public function finish(int $stepid): void
    {
        $this->Experiments->canOrExplode('write');

        $sql = "UPDATE experiments_steps SET finished = !finished,
            finished_time = NOW()
            WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $stepid, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Get steps for an experiments
     *
     * @return array
     */
    public function readAll(): array
    {
        $sql = "SELECT * FROM experiments_steps WHERE item_id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Experiments->id, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Copy the steps from one experiment to an other.
     *
     * @param int $id The id of the original experiment
     * @param int $newId The id of the new experiment that will receive the steps
     * @return void
     */
    public function duplicate(int $id, int $newId): void
    {
        $stepsql = "SELECT body FROM experiments_steps WHERE item_id = :id";
        $stepreq = $this->Db->prepare($stepsql);
        $stepreq->bindParam(':id', $id, PDO::PARAM_INT);
        if ($stepreq->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        while ($steps = $stepreq->fetch()) {
            $sql = "INSERT INTO experiments_steps (item_id, body) VALUES(:item_id, :body)";
            $req = $this->Db->prepare($sql);
            $req->execute(array(
                'item_id' => $newId,
                'body' => $steps['body']
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
        $this->Experiments->canOrExplode('write');

        $sql = "DELETE FROM experiments_steps WHERE id= :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Delete all the steps for an experiment
     * Now handled by cascade
     *
     * @return void
     */
    public function destroyAll(): void
    {
    }
}
