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

use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Params\TagParam;
use Elabftw\Services\TeamsHelper;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

/**
 * All about the tag
 */
final class Tags extends AbstractRest
{
    use SetIdTrait;

    public function __construct(public AbstractEntity $Entity, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('%s%d/tags/', $this->Entity->getApiPath(), $this->Entity->id ?? 0);
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        // check if we can actually create tags (for non-admins)
        $teamConfigArr = (new Teams($this->Entity->Users, $this->Entity->Users->team))->readOne();
        $TeamsHelper = new TeamsHelper($this->Entity->Users->team ?? 0);
        $canCreate = $teamConfigArr['user_create_tag'] === 1 || $TeamsHelper->isAdminInTeam($this->Entity->Users->userData['userid']);
        $tags = array();
        if (isset($reqBody['tag']) && is_string($reqBody['tag'])) {
            $tags = array($reqBody['tag']);
        }
        if (isset($reqBody['tags']) && is_array($reqBody['tags'])) {
            $tags = $reqBody['tags'];
        }

        $id = 0;
        foreach ($tags as $tag) {
            $id = $this->create(new TagParam($tag), $canCreate);
        }
        return $id;
    }

    #[Override]
    public function readOne(): array
    {
        return (new TeamTags($this->Entity->Users, $this->id))->readOne();
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = 'SELECT DISTINCT tag, tags2entity.tag_id, (tags_id IS NOT NULL) AS is_favorite FROM tags2entity LEFT JOIN tags ON (tags2entity.tag_id = tags.id) LEFT JOIN favtags2users ON (favtags2users.users_id = :userid AND favtags2users.tags_id = tags.id)
            WHERE item_id = :item_id AND item_type = :item_type ORDER BY tag';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':item_type', $this->Entity->entityType->value);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    /**
     * Copy the tags from one experiment/item to an other.
     *
     * @param int $newId The id of the new experiment/item that will receive the tags
     * @param bool $toExperiments convert to experiments type (when creating from tpl)
     */
    public function copyTags(int $newId, bool $toExperiments = false, bool $toItems = false): void
    {
        $tags = $this->readAll();
        $insertSql = 'INSERT INTO tags2entity (item_id, item_type, tag_id) VALUES (:item_id, :item_type, :tag_id)';
        $insertReq = $this->Db->prepare($insertSql);
        $entityType = $this->Entity->entityType;
        // an experiment template transforms into an experiment
        if ($toExperiments) {
            $entityType = EntityType::Experiments;
        }
        // same with itemstypes -> items
        if ($toItems) {
            $entityType = EntityType::Items;
        }

        foreach ($tags as $tag) {
            $insertReq->bindParam(':item_id', $newId, PDO::PARAM_INT);
            $insertReq->bindValue(':item_type', $entityType->value);
            $insertReq->bindParam(':tag_id', $tag['tag_id'], PDO::PARAM_INT);
            $this->Db->execute($insertReq);
        }
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        return match ($action) {
            Action::Unreference => $this->unreference(),
            default => throw new ImproperActionException('Invalid action for tags.'),
        };
    }

    /**
     * Destroy all the tags for an item ID
     * Here the tag are not destroyed because it might be nice to keep the tags in memory
     * even when nothing is referencing it. Admin can manage tags anyway if it needs to be destroyed.
     */
    #[Override]
    public function destroy(): bool
    {
        $sql = 'DELETE FROM tags2entity WHERE item_id = :id AND item_type = :type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':type', $this->Entity->entityType->value);
        return $this->Db->execute($req);
    }

    /**
     * Create a tag
     */
    public function create(TagParam $params, bool $canCreate): int
    {
        $this->Entity->canOrExplode('write');

        $TeamTags = new TeamTags($this->Entity->Users);

        // make sure we can create a new tag in this team
        if ($TeamTags->exists($params) === false && $canCreate === false) {
            throw new ImproperActionException(_('Users cannot create tags.'));
        }
        $tagId = $TeamTags->create($params);
        // now link the tag with the entity
        $sql = 'INSERT INTO tags2entity (item_id, item_type, tag_id) VALUES (:item_id, :item_type, :tag_id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':item_type', $this->Entity->entityType->value);
        $req->bindParam(':tag_id', $tagId, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $tagId;
    }

    /**
     * Unreference a tag from an entity, and possibly delete it if it's the last of its kind
     */
    private function unreference(): array
    {
        $this->Entity->canOrExplode('write');

        $sql = 'DELETE FROM tags2entity WHERE tag_id = :tag_id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag_id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Entity->readOne();
    }
}
