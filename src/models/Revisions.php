<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CrudInterface;
use PDO;

/**
 * All about the revisions
 */
class Revisions implements CrudInterface
{
    /** @var int MIN_DELTA the min number of characters different between two versions to trigger save */
    private const MIN_DELTA = 20;

    /** @var Db $Db SQL Database */
    private $Db;

    /** @var AbstractEntity $Entity an instance of Experiments or Database */
    private $Entity;

    /**
     * Constructor
     *
     * @param AbstractEntity $entity
     */
    public function __construct(AbstractEntity $entity)
    {
        $this->Entity = $entity;
        $this->Db = Db::getConnection();
    }

    /**
     * Add a revision if the changeset is big enough
     *
     * @param string $body
     * @return void
     */
    public function create(string $body): void
    {
        // only save a revision if there is at least MIN_DELTA characters difference between the old version and the new one
        if (abs(\mb_strlen($this->Entity->entityData['body'] ?? '') - \mb_strlen($body)) > self::MIN_DELTA) {
            $sql = 'INSERT INTO ' . $this->Entity->type . '_revisions (item_id, body, userid)
                VALUES(:item_id, :body, :userid)';

            $req = $this->Db->prepare($sql);
            $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
            $req->bindParam(':body', $body);
            $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    /**
     * Get how many revisions we have
     *
     * @return int number of revisions existing
     */
    public function readCount(): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->Entity->type . '_revisions
             WHERE item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (int) $req->fetchColumn();
    }

    /**
     * Read all revisions for an item
     *
     * @return array
     */
    public function readAll(): array
    {
        $sql = 'SELECT ' . $this->Entity->type . "_revisions.*,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM " . $this->Entity->type . '_revisions
            LEFT JOIN users ON (users.userid = ' . $this->Entity->type . '_revisions.userid)
            WHERE item_id = :item_id ORDER BY savedate DESC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Restore a revision
     *
     * @param int $revId The id of the revision we want to restore
     * @return void
     */
    public function restore(int $revId): void
    {
        // check for lock
        if ($this->isLocked()) {
            throw new ImproperActionException(_('You cannot restore a revision of a locked item!'));
        }

        $body = $this->readRev($revId);

        $sql = 'UPDATE ' . $this->Entity->type . ' SET body = :body WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':body', $body);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Not implemented
     *
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        return;
    }

    /**
     * Get the body of a revision
     *
     * @param int $revId The id of the revision
     * @return string
     */
    private function readRev(int $revId)
    {
        $sql = 'SELECT body FROM ' . $this->Entity->type . '_revisions WHERE id = :rev_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':rev_id', $revId, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchColumn();
        if ($res === false || $res === null) {
            return '';
        }
        return $res;
    }

    /**
     * Check if item is locked before restoring it
     *
     * @return bool
     */
    private function isLocked(): bool
    {
        $sql = 'SELECT locked FROM ' . $this->Entity->type . ' WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $locked = $req->fetch();

        return $locked['locked'] == 1;
    }
}
