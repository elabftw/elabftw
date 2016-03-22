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

    /** id of the experiment */
    private $id;

    /** current user */
    private $userid;

    /**
     * Constructor
     *
     */
    public function __construct($id, $userid)
    {
        $this->id = Tools::checkId($id);
        if ($this->id === false) {
            throw new Exception(_('The id parameter is not valid!'));
        }
        $this->userid = $userid;

        $this->pdo = Db::getConnection();
    }

    /**
     * Read an experiment
     *
     * @throws Exception if empty results
     * @return array
     */
    public function read()
    {
        $sql = "SELECT experiments.*, status.color, status.name FROM experiments
            LEFT JOIN status ON experiments.status = status.id
            WHERE experiments.id = :id ";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->execute();

        if ($req->rowCount() === 0) {
            throw new Exception('Nothing to show with this id.');
        }

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
    public function updateVisibility($visibility)
    {
        $sql = "UPDATE experiments SET visibility = :visibility WHERE userid = :userid AND id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':visibility', $visibility);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

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
    public function updateStatus($status)
    {
        $sql = "UPDATE experiments SET status = :status WHERE userid = :userid AND id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':status', $status, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($req->execute()) {
            // get the color of the status to return and update the css
            $statusClass = new \Elabftw\Elabftw\Status();
            return $statusClass->readColor($status);
        } else {
            return '0';
        }
    }

    /**
     * Add a link to an experiment
     *
     * @param int $link ID of database item
     * @param int $experiment ID of the experiment
     * @param int $userid used to check we own the experiment
     * @throws Exception
     * @return bool
     */
    public function createLink($link)
    {
        // check link is int and experiment is owned by user
        if (!is_pos_int($link) ||
            !is_owned_by_user($this->id, 'experiments', $this->userid)) {
            throw new Exception('Error adding link');
        }

        $sql = "INSERT INTO experiments_links (item_id, link_id) VALUES(:item_id, :link_id)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':link_id', $link, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Get links for an experiments
     *
     * @param int $experiment
     * @return array
     */
    public function readLink()
    {
        $sql = "SELECT items.id AS itemid,
            experiments_links.id AS linkid,
            experiments_links.*,
            items.*,
            items_types.*
            FROM experiments_links
            LEFT JOIN items ON (experiments_links.link_id = items.id)
            LEFT JOIN items_types ON (items.type = items_types.id)
            WHERE experiments_links.item_id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Delete a link
     *
     * @param int $link ID of our link
     * @param int $experiment ID of the experiment
     * @param int $userid used to check we own the experiment
     * @return bool
     */
    public function destroyLink($link)
    {
        if (!is_pos_int($link) ||
            !is_owned_by_user($this->id, 'experiments', $this->userid)) {
            throw new Exception('Error removing link');
        }
        $sql = "DELETE FROM experiments_links WHERE id= :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $link, PDO::PARAM_INT);

        return $req->execute();
    }
}
