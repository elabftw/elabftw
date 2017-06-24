<?php
/**
 * \Elabftw\Elabftw\Todolist
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;
use Exception;

/**
 * All about the todolist
 */
class ExperimentsSteps extends AbstractTodolist
{

    /** id of our experiment */
    private $experimentId;

    /**
    * table name 'experiments_steps'
    */
    protected function getTableName(){
        return "experiments_steps";
    }

    /**
     * Gimme a userid and an experiment id
     *
     * @param int $userid
     * @param int $experimentId
     */
    public function __construct($userid, $experimentId)
    {
        parent::__construct($userid);
        $this->experimentId = $experimentId;
        $this->userid = $userid;
    }

     /**
     * Creates an experiment step
     * 
     * @param string $body
     * @return int the id of the created experiment step
     */
    public function create($body)
    {
        $sql = "INSERT INTO " . $this->getTableName() . "(experiment_id, body, userid)
            VALUES(:experiment_id, :body, :userid)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':experiment_id', $this->experimentId);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->userid);

        if (!$req->execute()) {
            throw new Exception('Error inserting experiment item!');
        }

        return $this->pdo->lastInsertId();
    }

    /**
     * Select all experiment steps for a user per experiment
     *
     * @return array
     */
    public function readAll()
    {
        $sql = "SELECT id, body, creation_time FROM " . $this->getTableName() . " WHERE userid = :userid AND experiment_id = :experiment_id ORDER BY ordering ASC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $this->userid);
        $req->bindParam(':experiment_id', $this->experimentId);
        $req->execute();

        return $req->fetchAll();
    }

     /**
     * Clear all todoitems from the todolist
     *
     * @return bool
     */
    public function destroyAll()
    {
        $sql = "DELETE FROM " . $this->getTableName() . " WHERE userid = :userid AND experiment_id = :experiment_id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $this->userid);
        $req->bindParam(':experiment_id', $this->experimentId);

        return $req->execute();
    }
}