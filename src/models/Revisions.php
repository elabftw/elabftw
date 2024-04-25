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

use DateTimeImmutable;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Traits\SetIdTrait;
use PDO;

use function count;
use function mb_strlen;

/**
 * All about the revisions
 */
class Revisions implements RestInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(private AbstractEntity $Entity, private int $maxRevisions = 10, private int $minDelta = 100, private int $minDays = 23, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->id = $id;
    }

    public function getPage(): string
    {
        return sprintf('api/v2/%s/%d/revisions/', $this->Entity->page, $this->Entity->id ?? 0);
    }

    public function postAction(Action $action, array $reqBody): int
    {
        if ($this->Entity instanceof ItemsTypes) {
            return 0;
        }
        $this->Entity->canOrExplode('write');

        if (!$this->satisfyDeltaConstraint($reqBody['body']) && !$this->satisfyTimeConstraint() && $this->readCount() > 0) {
            return 0;
        }

        // destroy the oldest revision if we're reaching the max count
        if ($this->maxRevisions !== 0 && ($this->readCount() >= $this->maxRevisions)) {
            $this->destroyOld();
        }
        $sql = 'INSERT INTO ' . $this->Entity->type . '_revisions (item_id, body, userid)
            VALUES(:item_id, :body, :userid)';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':body', $reqBody['body']);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);

        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    // the Action should be Replace, but we have only one so we don't check for it
    public function patch(Action $action, array $params): array
    {
        $this->Entity->canOrExplode('write');
        // check for lock
        if ($this->Entity->entityData['locked']) {
            throw new ImproperActionException(_('You cannot restore a revision of a locked item!'));
        }

        $rev = $this->readOne();

        $sql = 'UPDATE ' . $this->Entity->type . ' SET body = :body WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':body', $rev['body']);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $rev;
    }

    /**
     * Read all revisions for an item
     */
    public function readAll(): array
    {
        $sql = sprintf('SELECT %1$s_revisions.id, %1$s_revisions.content_type, %1$s_revisions.created_at,
            CONCAT(users.firstname, " ", users.lastname) AS fullname
            FROM %1$s_revisions
            LEFT JOIN users ON (users.userid = %1$s_revisions.userid)
            WHERE item_id = :item_id ORDER BY created_at DESC', $this->Entity->type);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function destroy(): bool
    {
        throw new ImproperActionException(_('Revisions cannot be deleted.'));
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

    public function readOne(): array
    {
        $sql = 'SELECT * FROM ' . $this->Entity->type . '_revisions WHERE id = :rev_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':rev_id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $entityData = $this->Db->fetch($req);
        // add the body as html
        $entityData['body_html'] = $entityData['body'];
        // convert from markdown only if necessary
        if ($entityData['content_type'] === AbstractEntity::CONTENT_MD) {
            $entityData['body_html'] = Tools::md2html($entityData['body'] ?? '');
        }
        return $entityData;
    }

    /**
     * Get how many revisions we have
     */
    private function readCount(): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->Entity->type . '_revisions
             WHERE item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (int) $req->fetchColumn();
    }

    /**
     * Destroy old revisions
     *
     * @param int $num number of old revisions to destroy
     */
    private function destroyOld(int $num = 1): void
    {
        $oldestRevisions = array_slice(array_reverse($this->readAll()), 0, $num);
        $sql = 'DELETE FROM ' . $this->Entity->type . '_revisions WHERE id = :id';
        $req = $this->Db->prepare($sql);
        foreach ($oldestRevisions as $revision) {
            $req->bindParam(':id', $revision['id'], PDO::PARAM_INT);
            $this->Db->execute($req);
        }
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
        $lastchange = new DateTimeImmutable($this->Entity->entityData['modified_at'] ?? 'now');
        $now = new DateTimeImmutable();
        $interval = $lastchange->diff($now);
        return ((int) $interval->format('%a')) >= $this->minDays;
    }
}
