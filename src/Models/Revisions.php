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
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\BodyContentType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

use function mb_strlen;

/**
 * All about the revisions
 */
final class Revisions extends AbstractRest
{
    use SetIdTrait;

    public function __construct(private AbstractEntity $Entity, private int $maxRevisions = 10, private int $minDelta = 100, private int $minDays = 23, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('%s%d/revisions/', $this->Entity->getApiPath(), $this->Entity->id ?? 0);
    }

    public function create(string $body): int
    {
        $this->Entity->canOrExplode('write');

        if (!$this->satisfyDeltaConstraint($body) && !$this->satisfyTimeConstraint() && $this->readCount() > 0) {
            return 0;
        }

        $inserted = $this->dbInsert($body);
        $this->destroyOld();
        return $inserted;
    }

    public function dbInsert(?string $body): int
    {
        // don't bother if the body is empty
        if ($body === null || $body === '') {
            return 0;
        }
        $sql = 'INSERT INTO ' . $this->Entity->entityType->value . '_revisions (item_id, body, userid)
            VALUES(:item_id, :body, :userid)';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);

        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    // the Action should be Replace, but we have only one so we don't check for it
    #[Override]
    public function patch(Action $action, array $params): array
    {
        $this->Entity->canOrExplode('write');
        // check for lock
        if ($this->Entity->entityData['locked']) {
            throw new ImproperActionException(_('You cannot restore a revision of a locked item!'));
        }

        $rev = $this->readOne();

        $sql = 'UPDATE ' . $this->Entity->entityType->value . ' SET body = :body WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':body', $rev['body']);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $rev;
    }

    /**
     * Read all revisions for an item
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $this->Entity->canOrExplode('read');
        $sql = sprintf('SELECT %1$s_revisions.id, %1$s_revisions.content_type, %1$s_revisions.created_at,
            CONCAT(users.firstname, " ", users.lastname) AS fullname
            FROM %1$s_revisions
            LEFT JOIN users ON (users.userid = %1$s_revisions.userid)
            WHERE item_id = :item_id ORDER BY created_at DESC', $this->Entity->entityType->value);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    #[Override]
    public function readOne(): array
    {
        $this->Entity->canOrExplode('read');
        $sql = 'SELECT * FROM ' . $this->Entity->entityType->value . '_revisions WHERE id = :rev_id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':rev_id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $entityData = $this->Db->fetch($req);
        // add the body as html
        $entityData['body_html'] = $entityData['body'];
        // convert from markdown only if necessary
        if ($entityData['content_type'] === BodyContentType::Markdown->value) {
            $entityData['body_html'] = Tools::md2html($entityData['body'] ?? '');
        }
        return $entityData;
    }

    /**
     * Get how many revisions we have
     */
    private function readCount(): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->Entity->entityType->value . '_revisions
             WHERE item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (int) $req->fetchColumn();
    }

    /**
     * Destroy old revisions when number of revisions is more than maxRevisions
     */
    private function destroyOld(): bool
    {
        // skip if we allow infinite revisions
        if ($this->maxRevisions === 0) {
            return true;
        }
        $table = $this->Entity->entityType->value . '_revisions';
        $sql = 'DELETE r FROM ' . $table . ' AS r
            JOIN (
                SELECT id,
                       ROW_NUMBER() OVER (
                           PARTITION BY item_id
                           ORDER BY id DESC
                       ) AS rn
                FROM ' . $table . '
                WHERE item_id = :item_id
            ) AS ranked
              ON ranked.id = r.id
            WHERE r.item_id = :item_id
              AND ranked.rn > :keep';

        $req = $this->Db->prepare($sql);
        $req->bindValue(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':keep', $this->maxRevisions, PDO::PARAM_INT);
        return $this->Db->execute($req);
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
