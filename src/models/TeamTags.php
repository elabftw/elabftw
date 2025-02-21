<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Params\TagParam;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

/**
 * All about the tag but seen from a team perspective, not an entity
 */
final class TeamTags extends AbstractRest
{
    use SetIdTrait;

    public function __construct(public Users $Users, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('api/v2/teams/%d/tags/', $this->Users->userData['team']);
    }

    // look if the tag exists already
    public function exists(TagParam $params): bool
    {
        return (bool) $this->getTagIdFromTag($params);
    }

    /**
     * This will return the id of an existing tag in the team if it exists already
     */
    public function create(TagParam $params): int
    {
        $sql = 'INSERT INTO tags (tag, team) VALUES(:tag, :team) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindValue(':tag', $params->getContent());
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    /**
     * Create a new tag in that team
     */
    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        if (!$this->Users->isAdmin) {
            throw new IllegalActionException('Only an admin can do this!');
        }
        $tag = $reqBody['tag'] ?? throw new ImproperActionException('Missing required tag key!');
        return $this->create(new TagParam($tag));
    }

    #[Override]
    public function readOne(): array
    {
        $sql = 'SELECT tags.id, (tags_id IS NOT NULL) AS is_favorite, COUNT(tags2entity.id) AS item_count, tags.tag, tags.team
            FROM tags LEFT JOIN tags2entity ON tags2entity.tag_id = tags.id
            LEFT JOIN favtags2users ON (favtags2users.users_id = :userid AND favtags2users.tags_id = tags.id)
            WHERE team = :team AND tags.id = :id HAVING tags.id IS NOT NULL';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    /**
     * Read all the tags from team. This one can be called from api and will filter based on q param in query
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $queryParams ??= $this->getQueryParams();
        $query = $queryParams->getQuery()->getString('q');
        $sql = 'SELECT tags.id, (tags_id IS NOT NULL) AS is_favorite, COUNT(tags2entity.id) AS item_count, tags.tag, tags.team
            FROM tags LEFT JOIN tags2entity ON tags2entity.tag_id = tags.id
            LEFT JOIN favtags2users ON (favtags2users.users_id = :userid AND favtags2users.tags_id = tags.id)
            WHERE team = :team AND tags.tag LIKE :query GROUP BY tags.id ORDER BY tag';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindValue(':query', '%' . $query . '%');
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        if (!$this->Users->isAdmin) {
            throw new IllegalActionException('Only an admin can do this!');
        }
        return match ($action) {
            Action::UpdateTag => $this->updateTag(new TagParam($params['tag'])),
            default => throw new ImproperActionException('Invalid action for tags.'),
        };
    }

    /**
     * Destroy a tag completely. Unreference it from everywhere and then delete it
     */
    #[Override]
    public function destroy(): bool
    {
        if (!$this->Users->isAdmin) {
            throw new IllegalActionException('Only an admin can delete a tag!');
        }
        // first unreference the tag
        $sql = 'DELETE FROM tags2entity WHERE tag_id = :tag_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag_id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        // now delete it from the tags table
        $sql = 'DELETE FROM tags WHERE id = :tag_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag_id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function getTagIdFromTag(TagParam $params): int
    {
        $sql = 'SELECT id FROM tags WHERE tag = :tag AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindValue(':tag', $params->getContent());
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Take a list of tags id and deduplicate them
     * Update the references and delete the tags from the tags table
     *
     * @param string $idsList example: 23,42,1337
     */
    private function deduplicateFromIdsList(string $idsList): void
    {
        // convert the string list into an array
        $idsArr = explode(',', $idsList);
        // pop one out and keep this one
        $idToKeep = array_pop($idsArr);

        $sql = 'UPDATE tags2entity SET tag_id = :target_tag_id WHERE tag_id = :tag_id';
        $updateReq = $this->Db->prepare($sql);
        $updateReq->bindParam(':target_tag_id', $idToKeep, PDO::PARAM_INT);
        $sql = 'DELETE FROM tags WHERE id = :id';
        $deleteReq = $this->Db->prepare($sql);

        foreach ($idsArr as $id) {
            // update the references with the id that we keep
            $updateReq->bindParam(':tag_id', $id, PDO::PARAM_INT);
            $this->Db->execute($updateReq);

            // and delete that id from the tags table
            $deleteReq->bindParam(':id', $id, PDO::PARAM_INT);
            $this->Db->execute($deleteReq);
        }
    }

    private function updateTag(TagParam $params): array
    {
        // if the tag exists already the SQL Update statement will throw an error because of the unique key tag/team
        // what we want to do is to assign entries with the old tag to the new tag id, and then delete the old tag
        $id = $this->getTagIdFromTag($params);
        if ($id > 0) {
            $this->deduplicateFromIdsList(sprintf('%d,%d', $this->id ?? throw new ImproperActionException('Missing id for tag'), $id));
            return $this->readAll();
        }

        // use the team in the sql query to prevent one admin from editing tags from another team
        $sql = 'UPDATE tags SET tag = :tag WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindValue(':tag', $params->getContent());
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);

        $this->Db->execute($req);
        return $this->readAll();
    }
}
