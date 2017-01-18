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

    /** our team */
    public $team;

    /** id of our entity */
    public $id;

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

    public $canRead = false;
    public $canWrite = false;

    /** experiments or items */
    public $type;

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
        $this->setPermissions($this->id);
    }

    /**
     * Check if an item has a file attached.
     *
     * @return bool Return false if there is now file attached
     */
    public function hasAttachment()
    {
        $sql = "SELECT COUNT(*) FROM uploads
            WHERE item_id = :item_id AND type = :type LIMIT 1";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $this->id);
        $req->bindParam(':type', $this->type);
        $req->execute();

        return $req->fetchColumn() != 0;
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
     * Verify we can read/write an item
     *
     * @param int $id
     * @throws Exception
     */
    private function setPermissions($id)
    {
        $this->pdo = Db::getConnection();

        // reset values
        $this->canRead = false;
        $this->canWrite = false;

        $sql = "SELECT userid FROM " . $this->type . " WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id, \PDO::PARAM_INT);
        $req->execute();
        $theUser = $req->fetchColumn();

        if ($this->type === 'experiments') {
            // if we own the experiment, we have read/write rights on it for sure
            if ($theUser === $_SESSION['userid']) {
                $this->canRead = true;
                $this->canWrite = true;

            // admin can view any experiment
            } elseif (($theUser != $_SESSION['userid']) && $_SESSION['is_admin']) {
                $this->canRead = true;

            // if we don't own the experiment (and we are not admin), we need to check the visibility
            } elseif (($theUser != $_SESSION['userid']) && !$_SESSION['is_admin']) {
                $sql = "SELECT visibility, team FROM experiments WHERE id = :id";
                $req = $this->pdo->prepare($sql);
                $req->bindParam(':id', $id, \PDO::PARAM_INT);
                $req->execute();
                $experiment = $req->fetch();

                $validArr = array(
                    'public',
                    'organization'
                );

                // if the vis. setting is public or organization, we can see it for sure
                if (in_array($experiment['visibility'], $validArr)) {
                    $this->canRead = true;
                }

                // if the vis. setting is team, check we are in the same team than the item
                if (($experiment['visibility'] === 'team') && ($experiment['team'] == $_SESSION['team_id'])) {
                    $this->canRead = true;
                }

                // if the vis. setting is a team group, check we are in the group
                if (Tools::checkId($experiment['visibility'])) {
                    $TeamGroups = new $TeamGroups($_SESSION['team_id']);
                    if ($TeamGroups->isInTeamGroup($theUser, $visibility)) {
                        $this->canRead = true;
                    }
                }
            }

        } else {
            // for DB items, we only need to be in the same team

            // get the team of the userid of the item
            $sql = "SELECT team FROM users WHERE userid = :userid";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':userid', $theUser, \PDO::PARAM_INT);
            $req->execute();
            $theUserTeam = $req->fetchColumn();
            // we will compare the teams for DB items
            if ($theUserTeam == $_SESSION['team_id']) {
                $this->canRead = true;
                $this->canWrite = true;
            }
        }
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
            $req->bindParam(':team', $this->team);
            $req->bindParam(':id', $id, PDO::PARAM_INT);
            $success[] = $req->execute();
        }

        return !in_array(false, $success);
    }
}
