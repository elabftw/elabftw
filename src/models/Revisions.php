<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use function count;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\DestroyableInterface;
use function mb_strlen;
use PDO;

/**
 * All about the revisions
 */
class Revisions implements DestroyableInterface
{
    /** @var int MIN_DELTA the min number of characters different between two versions to trigger save */
    private const MIN_DELTA = 100;

    private Db $Db;

    private AbstractEntity $Entity;

    public function __construct(AbstractEntity $entity)
    {
        $this->Entity = $entity;
        $this->Db = Db::getConnection();
    }

    /**
     * Add a revision if the changeset is big enough
     */
    public function create(string $body): void
    {
        // only save a revision if there is at least MIN_DELTA characters difference between the old version and the new one
        $delta = abs(mb_strlen($this->Entity->entityData['body'] ?? '') - mb_strlen($body));
        if ($delta < self::MIN_DELTA) {
            return;
        }

        // destroy the oldest revision if we're reaching the max count
        $maxCount = $this->getMaxCount();
        if ($maxCount !== 0 && ($this->readCount() >= $maxCount)) {
            $this->destroyOld();
        }
        $sql = 'INSERT INTO ' . $this->Entity->type . '_revisions (item_id, body, userid)
            VALUES(:item_id, :body, :userid)';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Get how many revisions we have
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
     * Restore a revision from revision id
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

    public function destroy(int $id): bool
    {
        $sql = 'DELETE FROM ' . $this->Entity->type . '_revisions WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Make sure we don't store too many
     */
    public function prune(): int
    {
        $numberToRemove = 0;
        $current = count($this->readAll());
        $max = $this->getMaxCount();
        if ($current > $max) {
            $numberToRemove = $max - $current;
            $this->destroyOld($numberToRemove);
        }
        return $numberToRemove;
    }

    /**
     * Get the maximum number of revisions allowed to be stored
     */
    private function getMaxCount(): int
    {
        $Config = new Config();
        return (int) $Config->configArr['max_revisions'];
    }

    /**
     * Destroy old revisions
     *
     * @param int $num number of old revisions to destroy
     */
    private function destroyOld(int $num = 1): void
    {
        $oldestRevisions = array_slice(array_reverse($this->readAll()), 0, $num);
        foreach ($oldestRevisions as $revision) {
            $idToDelete = (int) $revision['id'];
            $this->destroy($idToDelete);
        }
    }

    /**
     * Get the body of a revision
     */
    private function readRev(int $revId): string
    {
        $sql = 'SELECT body FROM ' . $this->Entity->type . '_revisions WHERE id = :rev_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':rev_id', $revId, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchColumn();
        if ($res === false || $res === null) {
            return '';
        }
        return (string) $res;
    }

    /**
     * Check if item is locked before restoring it
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
