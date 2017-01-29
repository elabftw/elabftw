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
        // prevent reusing of old data from previous id
        unset($this->entityData);
    }

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
                (uploads.item_id IS NOT NULL) AS has_attachment, uploads.type FROM uploads GROUP BY uploads.item_id, uploads.type)
            AS uploads
            ON (uploads.up_item_id = " . $this->type . ".id AND uploads.type = '" . $this->type . "')
            ";
        $tagsSelect = ", GROUP_CONCAT(tagt.tag SEPARATOR '!----!') as tags, GROUP_CONCAT(tagt.id) as tags_id";

        if ($this instanceof Experiments) {
                $select = "SELECT DISTINCT " . $this->type . ".*,
                    status.color, status.name AS category, status.id AS category_id, uploads.up_item_id, uploads.has_attachment";

                $expCommentsSelect = ", experiments_comments.datetime";
                $from = "FROM experiments";

                $statusJoin = "LEFT JOIN status ON (status.id = experiments.status)";
                $commentsJoin = "LEFT JOIN experiments_comments ON (experiments_comments.exp_id = experiments.id)";
                $where = "WHERE experiments.team = :team";

            if (empty($this->idFilter)) {
                $sql = $select . ' ' .
                    $expCommentsSelect . ' ' .
                    $from . ' ';
            } else {
                $tagsJoin = "LEFT JOIN experiments_tags AS tagt ON (experiments.id = tagt.item_id)";
                $sql = $select . ' ' .
                    $tagsSelect . ' ' .
                    $from . ' ' .
                    $tagsJoin . ' ';
            }

            $sql .= $statusJoin . ' ' .
                $uploadsJoin . ' ' .
                $commentsJoin . ' ' .
                $where;

        } elseif ($this instanceof Database) {
            $sql = "SELECT DISTINCT items.id AS itemid,
                items.*, items_types.name AS category,
                items_types.color,
                items_types.id AS category_id,
                uploads.up_item_id, uploads.has_attachment,
                CONCAT(users.firstname, ' ', users.lastname) AS fullname";

                $from = "FROM items
                    LEFT JOIN items_types ON (items.type = items_types.id)
                    LEFT JOIN users ON (users.userid = items.userid)
                    LEFT JOIN items_tags AS tagt ON (items.id = tagt.item_id)";
                $where = "WHERE items.team = :team";

            if (!empty($this->idFilter)) {
                $sql .= $tagsSelect;
            }

            $sql .= ' ' . $from . ' ' . $uploadsJoin . ' ' . $where;

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
            " ORDER BY " . $this->order . " " . $this->sort . " " . $this->limit;

        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->execute();

        $itemsArr = $req->fetchAll();

        // reduce the dimension of the array if we have only one item (idFilter set)
        if (count($itemsArr) === 1 && !empty($this->idFilter)) {
            $item = $itemsArr[0];
            if ((strlen($item['tags'] > 1)) || true) {
                $tagsValueArr = explode('!----!', $item['tags']);
                $tagsKeyArr = explode(',', $item['tags_id']);
                $tagsArr = array_combine($tagsKeyArr, $tagsValueArr);
                $item['tagsArr'] = $tagsArr;
            }
            return $item;
        }
        return $itemsArr;
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
     * @param array $item one item array
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

            // admin can view any experiment
            } elseif (($item['userid'] != $this->Users->userid) && $isAdmin) {
                $permissions['read'] = true;

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
     * Update ordering for status, experiment templates or items types
     *
     * @param array $post POST
     * @return bool
     */
    public function updateOrdering($post)
    {
        $success = array();

        foreach ($post['ordering'] as $ordering => $id) {
            $id = explode('_', $id);
            $id = $id[1];
            // update the ordering
            $sql = "UPDATE " . $post['table'] . " SET ordering = :ordering WHERE id = :id AND team = :team";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
            $req->bindParam(':team', $this->Users->userData['team']);
            $req->bindParam(':id', $id, PDO::PARAM_INT);
            $success[] = $req->execute();
        }

        return !in_array(false, $success);
    }
}
