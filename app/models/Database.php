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
class Database extends Entity
{
    /** pdo object */
    protected $pdo;

    /** id of the item */
    public $id;

    /** id of the team */
    private $team;

    /**
     * Give me the team on init
     *
     * @param int $team
     * @param int|null $id
     */
    public function __construct($team, $id = null)
    {
        $this->pdo = Db::getConnection();
        $this->team = $team;

        if (!is_null($id)) {
            $this->setId($id);
        }

    }

    /**
     * Check and set id
     *
     * @param int $id
     */
    public function setId($id)
    {
        if (Tools::checkId($id) === false) {
            throw new Exception(_('The id parameter is not valid!'));
        }
        $this->id = $id;

        // permission check
        // you can only see items from your team
        if (!$this->isInTeam()) {
            throw new Exception(_('This section is out of your reach.'));
        }
    }

    /**
     * Create an item
     *
     * @param int $itemType What kind of item we want to create.
     * @return int the new id of the item
     */
    public function create($itemType)
    {
        // SQL to get template
        // TODO mv to template class
        $sql = "SELECT template FROM items_types WHERE id = :id";
        $get_tpl = $this->pdo->prepare($sql);
        $get_tpl->bindParam(':id', $itemType);
        $get_tpl->execute();
        $get_tpl_body = $get_tpl->fetch();

        // SQL for create DB item
        $sql = "INSERT INTO items(team, title, date, body, userid, type)
            VALUES(:team, :title, :date, :body, :userid, :type)";
        $req = $this->pdo->prepare($sql);
        $req->execute(array(
            'team' => $_SESSION['team_id'],
            'title' => 'Untitled',
            'date' => kdate(),
            'body' => $get_tpl_body['template'],
            'userid' => $_SESSION['userid'],
            'type' => $itemType
        ));

        return $this->pdo->lastInsertId();
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
