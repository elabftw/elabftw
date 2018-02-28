<?php
/**
 * \Elabftw\Elabftw\AbstractEntity
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
abstract class AbstractEntity
{
    use EntityTrait;

    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var Tags $Tags instance of Tags */
    public $Tags;

    /** @var Uploads $Uploads instance of Uploads */
    public $Uploads;

    /** @var string $type experiments or items */
    public $type;

    /** @var string $page will be defined in children classes */
    public $page;

    /** @var Users $Users instance of Users */
    public $Users;

    /** @var int $id id of our entity */
    public $id;

    /** @var string $idFilter inserted in sql */
    public $idFilter = '';

    /** @var string $useridFilter inserted in sql */
    public $useridFilter = '';

    /** @var string $bookableFilter inserted in sql */
    public $bookableFilter = '';

    /** @var string $ratingFilter inserted in sql */
    public $ratingFilter = '';

    /** @var string $teamFilter inserted in sql */
    public $teamFilter = '';

    /** @var string $visibilityFilter inserted in sql */
    public $visibilityFilter = '';

    /** @var string $titleFilter inserted in sql */
    public $titleFilter = '';

    /** @var string $dateFilter inserted in sql */
    public $dateFilter = '';

    /** @var string $bodyFilter inserted in sql */
    public $bodyFilter = '';

    /** @var string $categoryFilter inserted in sql */
    public $categoryFilter = '';

    /** @var string $tagFilter inserted in sql */
    public $tagFilter = '';

    /** @var string $queryFilter inserted in sql */
    public $queryFilter = '';

    /** @var string $order inserted in sql */
    public $order = 'date';

    /** @var string $sort inserted in sql */
    public $sort = 'DESC';

    /** @var string $limit limit for sql */
    public $limit = '';

    /** @var string $offset offset for sql */
    public $offset = '';

    /** @var array $entityData what you get after you ->read() */
    public $entityData;

    /** @var bool isReadOnly if we can read but not write to it */
    public $isReadOnly = false;

    /**
     * Constructor
     *
     * @param Users $users
     * @param int|null $id the id of the entity
     */
    public function __construct(Users $users, $id = null)
    {
        $this->Db = Db::getConnection();

        $this->Tags = new Tags($this);
        $this->Uploads = new Uploads($this);
        $this->Users = $users;

        if (!is_null($id)) {
            $this->setId($id);
        }

    }
    /**
     * Update status or item type
     *
     * @param int $category
     * @return bool
     */
    abstract public function updateCategory($category);

    /**
     * Duplicate an item
     *
     * @return int the new item id
     */
    abstract public function duplicate();

    /**
     * Destroy an item
     *
     * @return bool
     */
    abstract public function destroy();

    /**
     * Lock or unlock
     *
     * @return bool
     */
    abstract public function toggleLock();

    /**
     * Now that we have an id, load the data in entityData array
     *
     */
    protected function populate()
    {
        if (is_null($this->id)) {
            throw new Exception('No id was set.');
        }

        // load the entity in entityData array
        $this->entityData = $this->read();
    }

    /**
     * Read all from the entity
     * Optionally with filters
     * Here be dragons!
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
                uploads.type
            FROM uploads
            GROUP BY uploads.item_id, uploads.type)
            AS uploads
            ON (uploads.up_item_id = " . $this->type . ".id AND uploads.type = '" . $this->type . "')";

        $tagsSelect = ", GROUP_CONCAT(DISTINCT tagt.tag ORDER BY tagt.id SEPARATOR '|') as tags, GROUP_CONCAT(DISTINCT tagt.id) as tags_id";

        if ($this instanceof Experiments) {
            $select = "SELECT DISTINCT " . $this->type . ".*,
                status.color, status.name AS category, status.id AS category_id,
                uploads.up_item_id, uploads.has_attachment,
                experiments_comments.recent_comment,
                SUBSTRING_INDEX(GROUP_CONCAT(stepst.next_step SEPARATOR '|'), '|', 1) AS next_step,
                CONCAT(users.firstname, ' ', users.lastname) AS fullname";

            $from = "FROM experiments";

            $usersJoin = "LEFT JOIN users ON (experiments.userid = users.userid)";
            $stepsJoin = "LEFT JOIN (
                SELECT experiments_steps.item_id AS steps_item_id,
                experiments_steps.body AS next_step,
                experiments_steps.finished AS finished
                FROM experiments_steps)
                AS stepst ON (
                experiments.id = steps_item_id
                AND stepst.finished = 0)";
            $tagsJoin = "LEFT JOIN experiments_tags AS tagt ON (experiments.id = tagt.item_id)";
            $statusJoin = "LEFT JOIN status ON (status.id = experiments.status)";
            $commentsJoin = "LEFT JOIN (
                SELECT MAX(experiments_comments.datetime) AS recent_comment,
                    experiments_comments.exp_id FROM experiments_comments GROUP BY experiments_comments.exp_id
                ) AS experiments_comments
                ON (experiments_comments.exp_id = experiments.id)";
            $where = "WHERE experiments.team = :team";

            $sql = $select . ' ' .
                $tagsSelect . ' ' .
                $from . ' ' .
                $usersJoin . ' ' .
                $stepsJoin . ' ' .
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
            " GROUP BY id ORDER BY " . $this->order . " " . $this->sort . " " . $this->limit . " " . $this->offset;

        $req = $this->Db->prepare($sql);
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
     * Update an entity. The revision is saved before so it can easily compare old and new body.
     *
     * @param string $title
     * @param string $date
     * @param string $body
     * @return bool
     */
    public function update($title, $date, $body)
    {
        if (empty($this->entityData)) {
            $this->populate();
        }
        // don't update if locked
        if ($this->entityData['locked']) {
            return false;
        }

        // add a revision
        $Revisions = new Revisions($this);
        $Revisions->create($body);

        $title = Tools::checkTitle($title);
        $date = Tools::kdate($date);
        $body = Tools::checkBody($body);

        if ($this->type === 'experiments') {
            $sql = "UPDATE experiments SET
                title = :title,
                date = :date,
                body = :body
                WHERE id = :id";
        } else {
            $sql = "UPDATE items SET
                title = :title,
                date = :date,
                body = :body,
                userid = :userid
                WHERE id = :id";
        }

        $req = $this->Db->prepare($sql);
        $req->bindParam(':title', $title);
        $req->bindParam(':date', $date);
        $req->bindParam(':body', $body);
        if ($this->type != 'experiments') {
            $req->bindParam(':userid', $this->Users->userid);
        }
        $req->bindParam(':id', $this->id);

        return $req->execute();
    }

    /**
     * Set a limit for sql read
     *
     * @param int $num number of items to ignore
     * @return null
     */
    public function setLimit($num)
    {
        $this->limit = 'LIMIT ' . (int) $num;
    }

    /**
     * Add an offset to the displayed results
     *
     * @param int $num number of items to ignore
     * @return null
     */
    public function setOffset($num)
    {
        $this->offset = 'OFFSET ' . (int) $num;
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
     * @return array
     */
    public function canOrExplode($rw)
    {
        $permissions = $this->getPermissions();

        // READ ONLY?
        if ($permissions['read'] && !$permissions['write']) {
            $this->isReadOnly = true;
        }

        if (!$permissions[$rw]) {
            throw new Exception(Tools::error(true));
        }

        return $permissions;
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
        if (!isset($this->entityData) && !isset($item)) {
            $this->populate();
            if (empty($this->entityData)) {
                return array('read' => false, 'write' => false);
            }
        }
        // don't try to read() again if we have the item (for show where there are several items to check)
        if (!isset($item)) {
            $item = $this->entityData;
        }

        if ($this instanceof Experiments) {
            // if we own the experiment, we have read/write rights on it for sure
            if ($item['userid'] == $this->Users->userid) {
                return array('read' => true, 'write' => true);

            // it's not our experiment
            } else {
                // check if we're admin because admin can read/write all experiments of the team
                if ($this->Users->userData['is_admin']) {
                    // only admin of the same team can have write access
                    // check the team of the owner of the experiment
                    if ($item['team'] === $this->Users->userData['team']) {
                        return array('read' => true, 'write' => true);
                    }

                // if we don't own the experiment (and we are not admin), we need to check the visibility
                } else {

                    // if the vis. setting is public, we can see it for sure
                    if ($item['visibility'] === 'public') {
                        return array('read' => true, 'write' => false);
                    }

                    // if it's organization, we need to be logged in
                    if (($item['visibility'] === 'organization') && $this->Users->userid) {
                        return array('read' => true, 'write' => false);
                    }

                    // if the vis. setting is team, check we are in the same team than the $item
                    // we also check for anon because anon will have the same team as real team member
                    if (($item['visibility'] === 'team') &&
                        ($item['team'] == $this->Users->userData['team']) &&
                        (!$this->Users->userData['anon'])) {
                            return array('read' => true, 'write' => false);
                    }

                    // if the vis. setting is a team group, check we are in the group
                    if (Tools::checkId($item['visibility'])) {
                        $TeamGroups = new TeamGroups($this->Users);
                        if ($TeamGroups->isInTeamGroup($this->Users->userid, $item['visibility'])) {
                            return array('read' => true, 'write' => false);
                        }
                    }
                }
            }

        } elseif ($this instanceof Templates) {
            if ($item['userid'] === $this->Users->userid) {
                return array('read' => true, 'write' => true);
            }

        } elseif ($this instanceof Database) {
            // for DB items, we only need to be in the same team
            if ($item['team'] === $this->Users->userData['team']) {
                $ret = array('read' => true, 'write' => true);
                if (isset($this->Users->userData['anon'])) {
                    $ret['write'] = false;
                }
                return $ret;
            }
        }

        return array('read' => false, 'write' => false);
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
