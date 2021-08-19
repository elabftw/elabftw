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
use DateTimeImmutable;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\DestroyableInterface;
use Elabftw\Traits\SetIdTrait;
use function mb_strlen;
use PDO;

/**
 * All about the revisions
 */
class Revisions implements DestroyableInterface
{
    use SetIdTrait;

    private Db $Db;

    public function __construct(private AbstractEntity $Entity, private int $maxRevisions, private int $minDelta, private int $minDays, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->id = $id;
    }

    /**
     * Add a revision if the changeset is big enough
     */
    public function create(string $body): bool
    {
        $this->Entity->canOrExplode('write');

        if (!$this->satisfyDeltaConstraint($body) && !$this->satisfyTimeConstraint()) {
            return false;
        }

        // destroy the oldest revision if we're reaching the max count
        if ($this->maxRevisions !== 0 && ($this->readCount() >= $this->maxRevisions)) {
            $this->destroyOld();
        }
        $sql = 'INSERT INTO ' . $this->Entity->type . '_revisions (item_id, body, userid)
            VALUES(:item_id, :body, :userid)';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);

        return $this->Db->execute($req);
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

        return $this->Db->fetchAll($req);
    }

    /**
     * Restore a revision from revision id
     */
    public function restore(int $revId): void
    {
        // check for lock
        if ($this->Entity->entityData['locked']) {
            throw new ImproperActionException(_('You cannot restore a revision of a locked item!'));
        }

        $body = $this->readRev($revId);

        $sql = 'UPDATE ' . $this->Entity->type . ' SET body = :body WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':body', $body);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    public function destroy(): bool
    {
        $sql = 'DELETE FROM ' . $this->Entity->type . '_revisions WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Make sure we don't store too many
     */
    public function prune(): int
    {
        $numberToRemove = 0;
        $current = count($this->readAll());
        if ($current > $this->maxRevisions) {
            $numberToRemove = $this->maxRevisions - $current;
            $this->destroyOld($numberToRemove);
        }
        return $numberToRemove;
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
            $this->setId($idToDelete);
            $this->destroy();
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
     * Check if the minimum number of character changes constraint is satisfied
     * Returns true if there are enough changes
     */
    private function satisfyDeltaConstraint(string $body): bool
    {
        $delta = abs(mb_strlen($this->Entity->entityData['body'] ?? '') - mb_strlen($body));
        return $delta >= $this->minDelta;
    }

    /**
     * If the last change is too old, we'll want to create a revision regardless of delta constraint
     */
    private function satisfyTimeConstraint(): bool
    {
        $lastchange = new DateTimeImmutable($this->Entity->entityData['lastchange'] ?? 'now');
        $now = new DateTimeImmutable();
        $interval = $lastchange->diff($now);
        return ((int) $interval->format('%a')) >= $this->minDays;
    }
}
