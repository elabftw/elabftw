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
    public $id;

    /** id of the team */
    private $team;

    /**
     * Constructor, give me an id for an item and a team id
     *
     * @param int $id
     * @param int $team
     */
    public function __construct($id, $team)
    {
        $this->id = Tools::checkId($id);
        if ($this->id === false) {
            throw new Exception(_('The id parameter is not valid!'));
        }
        $this->team = $team;

        $this->pdo = Db::getConnection();

        // permission check
        // you can only see items from your team
        if (!$this->isInTeam()) {
            throw new Exception(_('This section is out of your reach.'));
        }

    }

    /**
     * Check if the item we want to view is in the team
     * Called by the constructor
     *
     */
    private function isInTeam()
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
     * Update a database item
     *
     * @param string $title
     * @param string $date
     * @param string $body
     * @param int $userid
     * @return bool
     */
    public function update($title, $date, $body, $userid)
    {
        $title = check_title($title);
        $date = check_date($date);
        $body = check_body($body);

        $sql = "UPDATE items
            SET title = :title,
            date = :date,
            body = :body,
            userid = :userid
            WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':title', $title);
        $req->bindParam(':date', $date);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $userid);
        $req->bindParam(':id', $this->id);

        // add a revision
        $revisions = new Revisions($this->id, 'items');
        if (!$revisions->create($body, $userid)) {
            throw new Exception(_('Error inserting revision.'));
        }

        return $req->execute();
    }
}
