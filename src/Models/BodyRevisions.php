<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Tools;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\Action;
use Elabftw\Enums\BodyContentType;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Params\ContentParams;
use Elabftw\Params\EntityParams;
use Elabftw\Services\BodyRevisionsStore;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

use function _;
use function hash;
use function sprintf;

/**
 * Complete history of the main text (body) of an entity
 *
 * Every body saved is kept forever, see BodyRevisionsStore for how they are stored.
 * Revisions cannot be modified or deleted: restoring one saves its body again.
 */
final class BodyRevisions extends AbstractRest
{
    use SetIdTrait;

    private BodyRevisionsStore $Store;

    private string $table;

    public function __construct(private AbstractEntity $Entity, ?int $id = null)
    {
        parent::__construct();
        $this->Store = new BodyRevisionsStore($this->Entity->entityType);
        $this->table = $this->Entity->entityType->value . '_body_revisions';
        $this->setId($id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('%s%d/revisions/', $this->Entity->getApiPath(), $this->Entity->id ?? 0);
    }

    /**
     * Record a body that was just saved. Permissions are checked by the caller that saved it.
     * The entityData of the entity must still hold the body that was replaced: it is the dictionary of the new revision.
     * With the current body, this makes sure it is recorded.
     *
     * @return int id of the new revision, or 0 if this body is already the latest revision
     */
    public function create(string $body): int
    {
        $entityId = $this->Entity->id ?? throw new ImproperActionException('No id was set!');
        $entityData = $this->Entity->entityData;
        $contentType = (int) $entityData['content_type'];
        $previousBody = (string) ($entityData['body'] ?? '');
        $latest = $this->Store->readLatest($entityId);

        // The replaced body was set without going through here (entity created, duplicated, imported...).
        // Record it first, as the body at the time of the last change of the entity, so the history has no hole.
        $isPreviousRecorded = $latest === null
            ? $previousBody === ''
            : $latest['body_sha256'] === hash('sha256', $previousBody);
        if (!$isPreviousRecorded) {
            $lastAuthor = $entityData['lastchangeby'] ?? $entityData['userid'] ?? null;
            $latest = $this->Store->insert(
                $entityId,
                $previousBody,
                $contentType,
                null,
                '',
                $lastAuthor === null ? null : (int) $lastAuthor,
                (string) $entityData['modified_at'],
            );
        }

        // only the latest revision is compared: going back to an older body is a change worth recording
        $isUnchanged = $latest === null
            ? $body === ''
            : $latest['body_sha256'] === hash('sha256', $body) && $latest['content_type'] === $contentType;
        if ($isUnchanged) {
            return 0;
        }

        return $this->Store->insert($entityId, $body, $contentType, $latest, $previousBody, (int) $this->Entity->Users->userData['userid'])['id'];
    }

    /**
     * All the revisions, newest first, without their body
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $this->canReadOrExplode();
        $sql = sprintf(
            "SELECT r.id, r.parent_id, r.depth, r.content_type, r.created_at, r.users_id AS userid,
                CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM %s AS r
            LEFT JOIN users ON (users.userid = r.users_id)
            WHERE r.entity_id = :entity_id
            ORDER BY r.created_at DESC, r.id DESC",
            $this->table,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    #[Override]
    public function readOne(): array
    {
        $this->canReadOrExplode();
        $id = $this->id ?? throw new ImproperActionException('No id was set!');
        $sql = sprintf(
            "SELECT r.id, r.entity_id AS item_id, r.parent_id, r.depth, r.content_type, r.created_at, r.users_id AS userid,
                CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM %s AS r
            LEFT JOIN users ON (users.userid = r.users_id)
            WHERE r.id = :id AND r.entity_id = :entity_id",
            $this->table,
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $revision = $this->Db->fetch($req);
        $revision['body'] = $this->Store->readBody($revision['item_id'], $id);
        $revision['body_html'] = $revision['content_type'] === BodyContentType::Markdown->value
            ? Tools::md2html($revision['body'])
            : $revision['body'];
        return $revision;
    }

    /**
     * Restore a revision: its body is saved again and becomes the latest revision
     * The Action should be Replace, but there is only one so it is not checked
     */
    #[Override]
    public function patch(Action $action, array $params): array
    {
        $this->Entity->canOrExplode(AccessType::Write);
        if ($this->Entity->entityData['locked']) {
            throw new ImproperActionException(_('You cannot restore a revision of a locked item!'));
        }
        $revision = $this->readOne();
        new Changelog($this->Entity)->create(new ContentParams('restore', sprintf('Restored from revision %d', $revision['id'])));
        $this->Entity->update(new EntityParams('body', $revision['body']));
        return $revision;
    }

    private function canReadOrExplode(): void
    {
        if ($this->Entity->Users instanceof AnonymousUser) {
            throw new ForbiddenException();
        }
        $this->Entity->canOrExplode(AccessType::Read);
    }
}
