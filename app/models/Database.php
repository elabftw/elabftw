<?php
/**
 * \Elabftw\Elabftw\Database
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
 * All about the database items
 */
class Database
{
    /** pdo object */
    private $pdo;

    /** id of the item */
    private $id;

    /**
     * Constructor
     *
     */
    public function __construct($id, $team)
    {
        $this->id = Tools::checkId($id);
        if ($this->id === false) {
            throw new Exception(_('The id parameter is not valid!'));
        }
        $this->pdo = Db::getConnection();
        $this->team = $team;

        if (!$this->isInTeam()) {
            throw new Exception(_('This section is out of your reach.'));
        }

    }

    /**
     * Check if the item we want to view is in the team
     *
     */
    public function isInTeam()
    {
        $sql = "SELECT team FROM items WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->execute();

        return $req->fetchColumn() == $this->team;
    }

    /**
     * Read an item
     *
     * @param int $id ID of the item
     * @throws Exception if empty results
     * @return array
     */
    public function read()
    {
        $sql = "SELECT items.id AS itemid,
            experiments_links.id AS linkid,
            experiments_links.*,
            items.*,
            items_types.*,
            users.lastname,
            users.firstname
            FROM items
            LEFT JOIN experiments_links ON (experiments_links.link_id = items.id)
            LEFT JOIN items_types ON (items.type = items_types.id)
            LEFT JOIN users ON (items.userid = users.userid)
            WHERE items.id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->execute();

        if ($req->rowCount() === 0) {
            throw new Exception('Nothing to show with this id.');
        }

        return $req->fetch();
    }

    /**
     * Update rating for a database item
     *
     * @param int $experiment Id of the experiment
     * @param string $visibility
     * @param int $userid Id of current user
     * @return bool
     * TODO
     */
    public function updateRating($experiment, $visibility, $userid)
    {
        $sql = "UPDATE experiments SET visibility = :visibility WHERE userid = :userid AND id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':visibility', $visibility);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindParam(':id', $experiment, PDO::PARAM_INT);

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
        $req->bindParam(':status', $status, PDO::PARAM_INT);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindParam(':id', $experiment, PDO::PARAM_INT);

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
    public function createLink($link, $experiment, $userid)
    {
        // check link is int and experiment is owned by user
        if (!is_pos_int($link) ||
            !is_owned_by_user($experiment, 'experiments', $userid)) {
            throw new Exception('Error adding link');
        }

        $sql = "INSERT INTO experiments_links (item_id, link_id) VALUES(:item_id, :link_id)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $experiment, PDO::PARAM_INT);
        $req->bindParam(':link_id', $link, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Get links for an experiments
     *
     * @param int $experiment
     * @return array
     */
    public function readLink($experiment)
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
        $req->bindParam(':id', $experiment, PDO::PARAM_INT);
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
    public function destroyLink($link, $experiment, $userid)
    {
        if (!is_pos_int($link) ||
            !is_pos_int($experiment) ||
            !is_owned_by_user($experiment, 'experiments', $userid)) {
            throw new Exception('Error removing link');
        }
        $sql = "DELETE FROM experiments_links WHERE id= :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $link, PDO::PARAM_INT);

        return $req->execute();
    }
}
