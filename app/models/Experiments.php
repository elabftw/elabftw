<?php
/**
 * \Elabftw\Elabftw\Experiments
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \PDO;
use \Exception;

/**
 * All about the experiments
 */
class Experiments
{
    /** pdo object */
    protected $pdo;

    /**
     * Constructor
     *
     * @throws Exception if user is not admin
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    /**
     * Read an experiment
     *
     * @return array
     */
    public function read($id)
    {
        $sql = "SELECT experiments.*, status.color FROM experiments LEFT JOIN status ON experiments.status = status.id
            WHERE experiments.id = :id ";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Update the visibility for an experiment
     *
     * @param int $experiment Id of the experiment
     * @param string $visibility
     * @param int $userid Id of current user
     * @return bool
     */
    public function updateVisibility($experiment, $visibility, $userid)
    {
        $sql = "UPDATE experiments SET visibility = :visibility WHERE userid = :userid AND id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':visibility', $visibility, \PDO::PARAM_INT);
        $req->bindParam(':userid', $userid, \PDO::PARAM_INT);
        $req->bindParam(':id', $experiment, \PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Update the status for an experiment
     *
     * @param int $experiment Id of the experiment
     * @param int $status Id of the status
     * @param int $userid Id of current user
     * @return string 0 on fail and color of new status on success
     */
    public function updateStatus($experiment, $status, $userid)
    {
        $sql = "UPDATE experiments SET status = :status WHERE userid = :userid AND id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':status', $status, \PDO::PARAM_INT);
        $req->bindParam(':userid', $userid, \PDO::PARAM_INT);
        $req->bindParam(':id', $experiment, \PDO::PARAM_INT);

        if ($req->execute()) {
            // get the color of the status to return and update the css
            $statusClass = new \Elabftw\Elabftw\Status();
            echo $statusClass->readColor($status);
        } else {
            echo '0';
        }
    }

    /**
     * Clear the logs
     *
     * @return bool
     */
    public function destroy()
    {

    }
}
