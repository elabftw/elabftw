<?php
/**
 * \Elabftw\Elabftw\Entity
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use PDO;

/**
 * The mother class of Experiments and Database
 */
class Entity
{
    use EntityTrait;

    /** pdo object */
    protected $pdo;

    /** experiments or items */
    public $type;

    /** instance of Users */
    public $Users;

    /** id of our entity */
    public $id;

    /** inserted in sql */
    public $idFilter = '';

    /** inserted in sql */
    public $useridFilter = '';

    /** inserted in sql */
    public $bookableFilter = '';

    /** inserted in sql */
    public $ratingFilter = '';

    /** inserted in sql */
    public $teamFilter = '';

    /** inserted in sql */
    public $visibilityFilter = '';

    /** inserted in sql */
    public $titleFilter = '';

    /** inserted in sql */
    public $dateFilter = '';

    /** inserted in sql */
    public $bodyFilter = '';

    /** inserted in sql */
    public $categoryFilter = '';

    /** inserted in sql */
    public $tagFilter = '';

    /** inserted in sql */
    public $queryFilter = '';

    /** inserted in sql */
    public $order = 'id';

    /** inserted in sql */
    public $sort = 'DESC';

    /** limit for sql */
    public $limit = '';

    /** what you get after you ->read() */
    public $entityData;

    /**
     * Now that we have an id, we can read the data and set the permissions
     *
     */
    public function populate()
    {
        if ($this instanceof Experiments || $this instanceof Database) {
            $this->entityData = $this->read();
        }
    }

    /**
     * Read all from the entity
     * Optionally with filters
     *
     * @return array
     */
    public function read()
    {
        if (!is_null($this->id)) {
            $this->idFilter = ' AND ' . $this->type . '.id = ' . $this->id;
        }

        $uploadsJoin = "LEFT JOIN (
            SELECT uploads.item_id AS up_item_id,
                (uploads.item_id IS NOT NULL) AS has_attachment,
                uploads.type FROM uploads GROUP BY uploads.item_id, uploads.type)
            AS uploads
            ON (uploads.up_item_id = " . $this->type . ".id AND uploads.type = '" . $this->type . "')";

        $tagsSelect = ", GROUP_CONCAT(tagt.tag SEPARATOR '|') as tags, GROUP_CONCAT(tagt.id) as tags_id";

        if ($this instanceof Experiments) {
            $select = "SELECT DISTINCT " . $this->type . ".*,
                status.color, status.name AS category, status.id AS category_id,
                    uploads.up_item_id, uploads.has_attachment";

            $expCommentsSelect = ", experiments_comments.recentComment";
            $from = "FROM experiments";

            $tagsJoin = "LEFT JOIN experiments_tags AS tagt ON (experiments.id = tagt.item_id)";
            $statusJoin = "LEFT JOIN status ON (status.id = experiments.status)";
            $commentsJoin = "LEFT JOIN (
                SELECT MAX(experiments_comments.datetime) AS recentComment,
                    experiments_comments.exp_id FROM experiments_comments GROUP BY experiments_comments.exp_id
                ) AS experiments_comments
                ON (experiments_comments.exp_id = experiments.id)";
            $where = "WHERE experiments.team = :team";

            $sql = $select . ' ' .
                $tagsSelect . ' ' .
                $expCommentsSelect . ' ' .
                $from . ' ' .
                $tagsJoin . ' ' .
                $statusJoin . ' ' .
                $uploadsJoin . ' ' .
                $commentsJoin . ' ' .
                $where;

        } elseif ($this instanceof Database) {
            $sql = "SELECT DISTINCT items.*, items_types.name AS category,
                items_types.color,
                items_types.id AS category_id,
                uploads.up_item_id, uploads.has_attachment,
                CONCAT(users.firstname, ' ', users.lastname) AS fullname";

            $from = "FROM items
                LEFT JOIN items_types ON (items.type = items_types.id)
                LEFT JOIN users ON (users.userid = items.userid)
                LEFT JOIN items_tags AS tagt ON (items.id = tagt.item_id)";
            $where = "WHERE items.team = :team";

            $sql .= ' ' . $tagsSelect . ' ' . $from . ' ' . $uploadsJoin . ' ' . $where;

        } else {
            throw new Exception('Nope.');
        }

        $sql .= $this->idFilter . ' ' .
            $this->useridFilter . ' ' .
            $this->titleFilter . ' ' .
            $this->dateFilter . ' ' .
            $this->bodyFilter . ' ' .
            $this->bookableFilter . ' ' .
            $this->categoryFilter . ' ' .
            $this->tagFilter . ' ' .
            $this->queryFilter . ' ' .
            $this->visibilityFilter . ' ' .
            " GROUP BY id ORDER BY " . $this->order . " " . $this->sort . " " . $this->limit;

        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->execute();

        $itemsArr = $req->fetchAll();

        // loop the array and only add the ones we can read
        $finalArr = array();
        foreach ($itemsArr as $item) {
            $permissions = $this->getPermissions($item);
            if ($permissions['read']) {
                $finalArr[] = $item;
            }
        }

        // reduce the dimension of the array if we have only one item (idFilter set)
        if (count($finalArr) === 1 && !empty($this->idFilter)) {
            $item = $finalArr[0];
            return $item;
        }
        return $finalArr;
    }

    /**
     * Update an entity
     *
     * @param string $title
     * @param string $date
     * @param string $body
     * @return bool
     */
    public function update($title, $date, $body)
    {
        $this->populate();
        // don't update if locked
        if ($this->entityData['locked']) {
            return false;
        }

        $title = Tools::checkTitle($title);
        $date = Tools::kdate($date);
        $body = Tools::checkBody($body);

        if ($this->type === 'experiments') {
            $sql = "UPDATE experiments SET
                title = :title,
                date = :date,
                body = :body
                WHERE userid = :userid
                AND id = :id";
        } else {
            $sql = "UPDATE items SET
                title = :title,
                date = :date,
                body = :body,
                userid = :userid
                WHERE id = :id";
        }

        $req = $this->pdo->prepare($sql);
        $req->bindParam(':title', $title);
        $req->bindParam(':date', $date);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Users->userid);
        $req->bindParam(':id', $this->id);

        // add a revision
        $Revisions = new Revisions($this);

        return $req->execute() && $Revisions->create($body);
    }

    /**
     * Set a limit for sql read
     *
     * @param int $num
     * @return null
     */
    public function setLimit($num)
    {
        $this->limit = 'LIMIT ' . (int) $num;
    }

    /**
     * Set the userid filter for read()
     *
     * @return null
     */
    public function setUseridFilter()
    {
        $this->useridFilter = ' AND ' . $this->type . '.userid = ' . $this->Users->userid;
    }

    /**
     * Check if we have the permission to read/write or throw an exception
     *
     * @param string $rw read or write
     * @throws Exception
     */
    public function canOrExplode($rw)
    {
        $permissions = $this->getPermissions();

        if (!$permissions[$rw]) {
            throw new Exception(Tools::error(true));
        }
    }

    /**
     * Verify we can read/write an item
     *
     * @param array|null $item one item array
     * @throws Exception
     * @return array
     */
    public function getPermissions($item = null)
    {
        $permissions = array('read' => false, 'write' => false);

        if (!isset($this->entityData) && !isset($item)) {
            $this->populate();
        }
        if (!isset($item)) {
            $item = $this->entityData;
        }

        $isAdmin = false;
        if (isset($_SESSION['is_admin']) && ($_SESSION['is_admin'] === '1')) {
            $isAdmin = true;
        }

        if ($this->type === 'experiments') {
            // if we own the experiment, we have read/write rights on it for sure
            if ($item['userid'] == $this->Users->userid) {
                $permissions['read'] = true;
                $permissions['write'] = true;

            // admin can view and write any experiment
            } elseif (($item['userid'] != $this->Users->userid) && $isAdmin) {
                $permissions['read'] = true;
                $permissions['write'] = true;

            // if we don't own the experiment (and we are not admin), we need to check the visibility
            } elseif (($item['userid'] != $this->Users->userid) && !$isAdmin) {
                $validArr = array(
                    'public',
                    'organization'
                );

                // if the vis. setting is public or organization, we can see it for sure
                if (in_array($item['visibility'], $validArr)) {
                    $permissions['read'] = true;
                }

                // if the vis. setting is team, check we are in the same team than the item
                if (($item['visibility'] === 'team') &&
                    ($item['team'] == $this->Users->userData['team'])) {
                    $permissions['read'] = true;
                }

                // if the vis. setting is a team group, check we are in the group
                if (Tools::checkId($item['visibility'])) {
                    $TeamGroups = new TeamGroups($this->Users->userData['team']);
                    if ($TeamGroups->isInTeamGroup($item['userid'], $item['visibility'])) {
                        $permissions['read'] = true;
                    }
                }
            }

        } else {
            // for DB items, we only need to be in the same team
            if ($item['team'] === $this->Users->userData['team']) {
                $permissions['read'] = true;
                $permissions['write'] = true;
            }
        }

        return $permissions;
    }

    /**
     * Get a list of experiments with title starting with $term and optional user filter
     *
     * @param string $term the query
     * @param bool $userFilter filter experiments for user or not
     * @return array
     */
    public function getExpList($term, $userFilter = false)
    {
        $Experiments = new Experiments($this->Users);
        $Experiments->titleFilter = " AND title LIKE '%$term%'";
        if ($userFilter) {
            $Experiments->setUseridFilter();
        }

        return $Experiments->read();
    }

    /**
     * Get a list of items with a filter on the $term
     *
     * @param string $term the query
     * @return array
     */
    public function getDbList($term)
    {
        $Database = new Database($this->Users);
        $Database->titleFilter = " AND title LIKE '%$term%'";

        return $Database->read();
    }

    /**
     * Get an array formatted for the Link list on experiments
     *
     * @param string $term the query
     * @return array
     */
    public function getLinkList($term)
    {
        $linksArr = array();
        $itemsArr = $this->getDbList($term);

        foreach ($itemsArr as $item) {
            $linksArr[] = $item['id'] . " - " . $item['category'] . " - " . substr($item['title'], 0, 60);
        }

        return $linksArr;
    }

    /**
     * Get an array of a mix of experiments and database items
     * for use with the mention plugin of tinymce (# and $ autocomplete)
     *
     * @param $term the query
     * @param bool $userFilter filter experiments for user or not
     * @return array
     */
    public function getMentionList($term, $userFilter = false)
    {
        $mentionArr = array();

        // add items from database
        $itemsArr = $this->getDbList($term);
        foreach ($itemsArr as $item) {
            $mentionArr[] = array("name" => "<a href='database.php?mode=view&id=" .
                $item['id'] . "'>" .
                substr($item['title'], 0, 60) .
                "</a>");
        }

        // complete the list with experiments
        // fix #191
        $experimentsArr = $this->getExpList($term, $userFilter);
        foreach ($experimentsArr as $item) {
            $mentionArr[] = array("name" => "<a href='experiments.php?mode=view&id=" .
                $item['id'] . "'>" .
                substr($item['title'], 0, 60) .
                "</a>");
        }

        return $mentionArr;
    }
}
