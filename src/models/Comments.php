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

use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Notifications\CommentCreated;
use Elabftw\Params\CommentParam;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

/**
 * All about the comments
 */
class Comments extends AbstractRest
{
    use SetIdTrait;

    protected int $immutable = 0;

    public function __construct(public AbstractEntity $Entity, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    public function getApiPath(): string
    {
        return sprintf('%s%d/comments/', $this->Entity->getApiPath(), $this->Entity->id ?? 0);
    }

    #[Override]
    public function readOne(): array
    {
        $this->Entity->canOrExplode('read');
        $sql = 'SELECT ' . $this->Entity->entityType->value . "_comments.*,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            users.firstname, users.lastname, users.orcid, users.email
            FROM " . $this->Entity->entityType->value . '_comments
            LEFT JOIN users ON (' . $this->Entity->entityType->value . '_comments.userid = users.userid)
            WHERE ' . $this->Entity->entityType->value . '_comments.id = :id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $this->Entity->canOrExplode('read');
        $sql = 'SELECT ' . $this->Entity->entityType->value . "_comments.*,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            users.firstname, users.lastname, users.orcid, users.email
            FROM " . $this->Entity->entityType->value . '_comments
            LEFT JOIN users ON (' . $this->Entity->entityType->value . '_comments.userid = users.userid)
            WHERE item_id = :id ORDER BY ' . $this->Entity->entityType->value . '_comments.created_at ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $this->update(new CommentParam($params['comment']));
        return $this->readOne();
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create(new CommentParam($reqBody['comment'] ?? throw new ImproperActionException('Missing comment field.')));
    }

    public function update(CommentParam $params): bool
    {
        $this->Entity->canOrExplode('read');
        $this->canWriteOrExplode();
        $sql = 'UPDATE ' . $this->Entity->entityType->value . '_comments SET
            comment = :content
            WHERE id = :id AND userid = :userid AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    #[Override]
    public function destroy(): bool
    {
        $this->canWriteOrExplode();
        $sql = 'DELETE FROM ' . $this->Entity->entityType->value . '_comments WHERE id = :id AND userid = :userid AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    protected function canWriteOrExplode(): void
    {
        $comment = $this->readOne();
        if ($comment['immutable'] === 1) {
            throw new ImproperActionException(Tools::error(true));
        }
    }

    protected function create(CommentParam $params): int
    {
        $sql = 'INSERT INTO ' . $this->Entity->entityType->value . '_comments(item_id, comment, userid, immutable)
            VALUES(:item_id, :content, :userid, :immutable)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':immutable', $this->immutable, PDO::PARAM_INT);

        $this->Db->execute($req);
        $this->createNotification();

        return $this->Db->lastInsertId();
    }

    /**
     * Create a notification to the experiment owner to alert a comment was posted
     * (issue #160). Only for an experiment we don't own.
     */
    protected function createNotification(): void
    {
        if ($this->Entity->entityData['userid'] === $this->Entity->Users->userData['userid']) {
            return;
        }

        /** @psalm-suppress PossiblyNullArgument */
        $Notif = new CommentCreated($this->Entity->entityType->toPage(), $this->Entity->id, $this->Entity->Users->userData['userid']);
        // target user is the owner of the entry
        $Notif->create($this->Entity->entityData['userid']);
    }
}
