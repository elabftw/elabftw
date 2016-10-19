<?php
/**
 * \Elabftw\Elabftw\Database
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
 * All about the database items
 */
class Database extends Entity
{
    /** pdo object */
    protected $pdo;

    /** inserted in sql */
    public $bookableFilter = '';

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
     * Create an item
     *
     * @param int $itemType What kind of item we want to create.
     * @param int $userid
     * @return int the new id of the item
     */
    public function create($itemType, $userid)
    {
        $itemsTypes = new ItemsTypes($this->team);

        // SQL for create DB item
        $sql = "INSERT INTO items(team, title, date, body, userid, type)
            VALUES(:team, :title, :date, :body, :userid, :type)";
        $req = $this->pdo->prepare($sql);
        $req->execute(array(
            'team' => $this->team,
            'title' => _('Untitled'),
            'date' => Tools::kdate(),
            'body' => $itemsTypes->read($itemType),
            'userid' => $userid,
            'type' => $itemType
        ));

        return $this->pdo->lastInsertId();
    }


    /**
     * Check if the item we want to view is in the team
     *
     * @return bool
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
     * @throws Exception if empty results
     * @return array
     */
    public function read()
    {
        // permission check
        // you can only see items from your team
        if (!$this->isInTeam()) {
            throw new Exception(Tools::error(true));
        }

        $sql = "SELECT DISTINCT items.id AS itemid,
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

        return $req->fetch();
    }

    /**
     * Read all items for a team
     * Optionally with filters
     *
     * @return array
     */
    public function readAll()
    {
        $sql = "SELECT DISTINCT items.id AS itemid, items.*, items_types.name, items_types.bgcolor, uploads.*
        FROM items
        LEFT JOIN items_types ON (items.type = items_types.id)
        LEFT JOIN items_tags ON (items.id = items_tags.item_id)
        LEFT JOIN (SELECT uploads.item_id AS attachment, uploads.type FROM uploads) AS uploads ON (uploads.attachment = items.id AND uploads.type = 'items')
        WHERE items.team = :teamid
        " . $this->bookableFilter . "
        " . $this->categoryFilter . "
        " . $this->tagFilter . "
        " . $this->queryFilter . "
        ORDER BY $this->order $this->sort $this->limit";

        $req = $this->pdo->prepare($sql);
        $req->bindParam(':teamid', $this->team);
        $req->execute();

        return $req->fetchAll();
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
        // permission check
        // you can only see items from your team
        if (!$this->isInTeam()) {
            throw new Exception(Tools::error(true));
        }
        $title = Tools::checkTitle($title);
        $date = Tools::kdate($date);
        $body = Tools::checkBody($body);

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
        $Revisions = new Revisions('items', $this->id, $userid);
        if (!$Revisions->create($body)) {
            throw new Exception(Tools::error());
        }

        return $req->execute();
    }

    /**
     * Update the rating of an item
     *
     * @param int $rating
     * @return bool
     */
    public function updateRating($rating)
    {
        if (!$this->isInTeam()) {
            throw new Exception(Tools::error(true));
        }

        $sql = 'UPDATE items SET rating = :rating WHERE id = :id';
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':rating', $rating, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id);

        return $req->execute();
    }

    /**
     * Duplicate an item
     *
     * @param int $userid
     * @return int $newId The id of the newly created item
     */
    public function duplicate($userid)
    {
        $item = $this->read();

        $sql = "INSERT INTO items(team, title, date, body, userid, type)
            VALUES(:team, :title, :date, :body, :userid, :type)";
        $req = $this->pdo->prepare($sql);
        $req->execute(array(
            'team' => $item['team'],
            'title' => $item['title'],
            'date' => Tools::kdate(),
            'body' => $item['body'],
            'userid' => $userid,
            'type' => $item['type']
        ));
        $newId = $this->pdo->lastInsertId();

        $tags = new Tags('items', $this->id);
        $tags->copyTags($newId);

        return $newId;
    }

    /**
     * Destroy a DB item
     *
     * @throws Exception
     * @return bool
     */
    public function destroy()
    {
        // we can only delete items from our team
        if (!$this->isInTeam()) {
            throw new Exception(Tools::error(true));
        }

        // to store the outcome of sql
        $result = array();

        // delete the database item
        $sql = "DELETE FROM items WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id);
        $result[] = $req->execute();

        $tags = new Tags('items', $this->id);
        $result[] = $tags->destroyAll();

        $uploads = new Uploads('items', $this->id);
        $result[] = $uploads->destroyAll();

        // delete links of this item in experiments with this item linked
        // get all experiments with that item linked
        $sql = "SELECT id FROM experiments_links WHERE link_id = :link_id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':link_id', $this->id);
        $result[] = $req->execute();

        while ($links = $req->fetch()) {
            $delete_sql = "DELETE FROM experiments_links WHERE id = :links_id";
            $delete_req = $this->pdo->prepare($delete_sql);
            $delete_req->bindParam(':links_id', $links['id']);
            $result[] = $delete_req->execute();
        }

        if (in_array(false, $result)) {
            throw new Exception('Error deleting item.');
        }

        return true;
    }

    /**
     * Lock or unlock an item
     *
     * @throws Exception
     * @return bool
     */
    public function toggleLock()
    {
        if (!$this->isInTeam()) {
            throw new Exception(Tools::error(true));
        }

        // get what is the current state
        $sql = "SELECT locked FROM items WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id);
        $req->execute();
        $locked = (int) $req->fetchColumn();
        if ($locked === 1) {
            $locked = 0;
        } else {
            $locked = 1;
        }

        // toggle
        $sql = "UPDATE items SET locked = :locked WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindValue(':locked', $locked);
        $req->bindParam(':id', $this->id);

        return $req->execute();
    }
}
