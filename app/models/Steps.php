<?php
/**
 * \Elabftw\Elabftw\Steps
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * All about the experiments steps
 */
class Steps
{
    /** pdo object */
    protected $pdo;

    /** instance of Experiments */
    public $Experiments;

    /**
     * Constructor
     *
     * @param Experiments $experiments
     */
    public function __construct(Experiments $experiments)
    {
        $this->pdo = Db::getConnection();
        $this->Experiments = $experiments;
    }

    /**
     * Add a step to an experiment
     *
     * @param string $body the text for the step
     * @return bool
     */
    public function create($body)
    {
        $sql = "INSERT INTO experiments_steps (item_id, body) VALUES(:item_id, :body)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $this->Experiments->id);
        $req->bindParam(':body', $body);

        return $req->execute();
    }

    /**
     * Toggle the finished column of a step
     *
     * @param int $stepid
     * @return bool
     */
    public function finish($stepid)
    {
        $sql = "UPDATE experiments_steps SET finished = !finished,
            finished_time = NOW()
            WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $stepid);

        return $req->execute();
    }

    /**
     * Get steps for an experiments
     *
     * @return array
     */
    public function read()
    {
        $sql = "SELECT * FROM experiments_steps WHERE item_id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->Experiments->id);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Copy the steps from one experiment to an other.
     *
     * @param int $id The id of the original experiment
     * @param int $newId The id of the new experiment that will receive the steps
     * @return null
     */
    public function duplicate($id, $newId)
    {
        $stepsql = "SELECT body FROM experiments_steps WHERE item_id = :id";
        $stepreq = $this->pdo->prepare($stepsql);
        $stepreq->bindParam(':id', $id);
        $stepreq->execute();

        while ($steps = $stepreq->fetch()) {
            $sql = "INSERT INTO experiments_steps (item_id, body) VALUES(:item_id, :body)";
            $req = $this->pdo->prepare($sql);
            $req->execute(array(
                'item_id' => $newId,
                'body' => $steps['body']
            ));
        }
    }

    /**
     * Delete a step
     *
     * @param int $stepId
     * @return bool
     */
    public function destroy($stepId)
    {
        $sql = "DELETE FROM experiments_steps WHERE id= :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $stepId);

        return $req->execute();
    }

    /**
     * Delete all the steps for an experiment
     *
     * @return bool
     */
    public function destroyAll()
    {
        $sql = "DELETE FROM experiments_steps WHERE item_id = :item_id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $this->Experiments->id);

        return $req->execute();
    }
}
