<?php
/**
 * \Elabftw\Elabftw\Revisions
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
 * All about the revisions
 */
class Revisions
{
    /** pdo object */
    private $pdo;

    /** an instance of Experiments or Database */
    private $Entity;

    /**
     * Constructor
     *
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->pdo = Db::getConnection();

        $this->Entity = $entity;
    }

    /**
     * Add a revision
     *
     * @param string $body
     * @return bool
     */
    public function create($body)
    {
        $sql = "INSERT INTO " . $this->Entity->type . "_revisions (item_id, body, userid) VALUES(:item_id, :body, :userid)";

        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Entity->Users->userid);

        return $req->execute();
    }

    /**
     * Get how many revisions we have
     *
     */
    public function readCount()
    {
        $sql = "SELECT COUNT(*) FROM " . $this->Entity->type . "_revisions
             WHERE item_id = :item_id ORDER BY savedate DESC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id);
        $req->execute();

        return (int) $req->fetchColumn();
    }


    /**
     * Output HTML for displaying revisions
     *
     */
    public function showCount()
    {
        $html = '';
        $count = $this->readCount();

        if ($count > 0) {
            $html .= "<span class='align_right'>";
            $html .= $count . " " . ngettext('revision available.', 'revisions available.', $count);
            $html .= " <a href='revisions.php?type=" . $this->Entity->type . "&item_id=" . $this->Entity->id . "'>" . _('Show history') . "</a>";
            $html .= "</span>";
        }

        return $html;
    }

    /**
     * Read all revisions for an item
     *
     * @return array
     */
    public function read()
    {
        $sql = "SELECT * FROM " . $this->Entity->type . "_revisions WHERE item_id = :item_id AND userid = :userid ORDER BY savedate DESC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id);
        $req->bindParam(':userid', $this->Entity->Users->userid);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Get the body of a revision
     *
     * @param int $revId The id of the revision
     * @return array
     */
    private function readRev($revId)
    {
        $sql = "SELECT body FROM " . $this->Entity->type . "_revisions WHERE id = :rev_id AND userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':rev_id', $revId);
        $req->bindParam(':userid', $this->Entity->Users->userid);
        $req->execute();

        return $req->fetchColumn();
    }

    /**
     * Check if item is locked before restoring it
     *
     * @throws Exception
     * @return bool
     */
    private function isLocked()
    {
        $sql = "SELECT locked FROM " . $this->Entity->type . " WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->execute();
        $locked = $req->fetch();

        return $locked['locked'] == 1;
    }

    /**
     * Restore a revision
     *
     * @param int $revId The id of the revision we want to restore
     * @throws Exception
     * @return bool
     */
    public function restore($revId)
    {
        // check for lock
        if ($this->isLocked()) {
            throw new Exception(_('You cannot restore a revision of a locked item!'));
        }

        $body = $this->readRev($revId);

        $sql = "UPDATE " . $this->Entity->type . " SET body = :body WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':body', $body);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);

        return $req->execute();
    }
}
