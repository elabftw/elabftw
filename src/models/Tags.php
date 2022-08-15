<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use function array_column;
use function count;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\TagParam;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Traits\SetIdTrait;
use function implode;
use PDO;

/**
 * All about the tag
 */
class Tags implements RestInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(public AbstractEntity $Entity, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->setId($id);
    }

    public function getPage(): string
    {
        return $this->Entity->getPage();
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create(new TagParam($reqBody['tag']));
    }

    /**
     * Create a tag
     */
    public function create(TagParam $params): int
    {
        $this->Entity->canOrExplode('write');

        $insertSql2 = 'INSERT INTO tags2entity (item_id, item_type, tag_id) VALUES (:item_id, :item_type, :tag_id)';
        $insertReq2 = $this->Db->prepare($insertSql2);
        // check if the tag doesn't exist already for the team
        $sql = 'SELECT id FROM tags WHERE tag = :tag AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':tag', $params->getContent());
        $req->bindParam(':team', $this->Entity->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $tagId = (int) $req->fetchColumn();

        // tag doesn't exist already
        if ($req->rowCount() === 0) {
            // check if we can actually create tags (for non-admins)
            $Teams = new Teams($this->Entity->Users, (int) $this->Entity->Users->userData['team']);
            $teamConfigArr = $Teams->readOne();
            if ($teamConfigArr['user_create_tag'] === 0 && $this->Entity->Users->userData['is_admin'] === 0) {
                throw new ImproperActionException(_('Users cannot create tags.'));
            }

            $insertSql = 'INSERT INTO tags (team, tag) VALUES (:team, :tag)';
            $insertReq = $this->Db->prepare($insertSql);
            $insertReq->bindValue(':tag', $params->getContent());
            $insertReq->bindParam(':team', $this->Entity->Users->userData['team'], PDO::PARAM_INT);
            $this->Db->execute($insertReq);
            $tagId = $this->Db->lastInsertId();
        }
        // now reference it
        $insertReq2->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $insertReq2->bindParam(':item_type', $this->Entity->type);
        $insertReq2->bindParam(':tag_id', $tagId, PDO::PARAM_INT);

        if ($insertReq2->execute() !== true) {
            throw new DatabaseErrorException();
        }

        return $tagId;
    }

    public function read(ContentParamsInterface $params): array
    {
        if ($params->getTarget() === 'list') {
            return $this->getList($params->getContent());
        }
        return $this->readAll();
    }

    public function readOne(): array
    {
        // not used
        return array();
    }

    /**
     * Read all the tags from team
     *
     * @param string|null $term The beginning of the input for tag autocomplete
     */
    public function readAll(?string $term = null): array
    {
        $tagFilter = '';
        if ($term !== null) {
            $tagFilter = " AND tags.tag LIKE '%$term%'";
        }
        $sql = "SELECT tag, id
            FROM tags
            WHERE team = :team
            $tagFilter
            ORDER BY tag ASC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Entity->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Copy the tags from one experiment/item to an other.
     *
     * @param int $newId The id of the new experiment/item that will receive the tags
     * @param bool $toExperiments convert to experiments type (when creating from tpl)
     */
    public function copyTags(int $newId, bool $toExperiments = false): void
    {
        $sql = 'SELECT tag_id FROM tags2entity WHERE item_id = :item_id AND item_type = :item_type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':item_type', $this->Entity->type);
        $this->Db->execute($req);
        if ($req->rowCount() > 0) {
            $insertSql = 'INSERT INTO tags2entity (item_id, item_type, tag_id) VALUES (:item_id, :item_type, :tag_id)';
            $insertReq = $this->Db->prepare($insertSql);

            $type = $this->Entity->type;
            // an experiment template transforms into an experiment
            if ($toExperiments) {
                $type = 'experiments';
            }

            while ($tags = $req->fetch()) {
                $insertReq->bindParam(':item_id', $newId, PDO::PARAM_INT);
                $insertReq->bindParam(':item_type', $type);
                $insertReq->bindParam(':tag_id', $tags['tag_id'], PDO::PARAM_INT);
                $this->Db->execute($insertReq);
            }
        }
    }

    public function patch(Action $action, array $params): array
    {
        match ($action) {
            Action::Unreference => $this->unreference(),
            default => throw new ImproperActionException('Invalid action for tags.'),
        };
        return $this->readOne();
    }

    /**
     * Destroy a tag completely. Unreference it from everywhere and then delete it
     */
    public function destroy(): bool
    {
        if ($this->Entity->Users->userData['is_admin'] !== 1) {
            throw new IllegalActionException('Only an admin can update a tag!');
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

    /**
     * Destroy all the tags for an item ID
     * Here the tag are not destroyed because it might be nice to keep the tags in memory
     * even when nothing is referencing it. Admin can manage tags anyway if it needs to be destroyed.
     */
    public function destroyAll(): bool
    {
        $sql = 'DELETE FROM tags2entity WHERE item_id = :id AND item_type = :type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);
        return $this->Db->execute($req);
    }

    /**
     * Get a list of entity id filtered by tags
     *
     * @param array<array-key, string> $tags tags from the query string
     */
    public function getIdFromTags(array $tags): array
    {
        $results = array();
        $sql = 'SELECT id FROM tags WHERE tag = :tag';
        $req = $this->Db->prepare($sql);
        foreach ($tags as $tag) {
            $req->bindParam(':tag', $tag);
            $req->execute();
            $res = $req->fetch();
            if ($res !== false) {
                $results[] = $res;
            }
        }
        $tagIds = array_column($results, 'id');
        if (empty($tagIds)) {
            return array();
        }

        // look for item ids that have all the tags not only one of them
        // note: you can't have a parameter for the IN clause
        $itemIds = array();
        $sql = 'SELECT item_id FROM `tags2entity` WHERE tag_id IN (' . implode(',', $tagIds) . ')
            AND item_type = :type GROUP BY item_id HAVING COUNT(DISTINCT tag_id) = :count';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':type', $this->Entity->type, PDO::PARAM_STR);
        $req->bindValue(':count', count($tagIds), PDO::PARAM_INT);
        $req->execute();
        foreach ($req->fetchAll() as $res) {
            $itemIds[] = (int) $res['item_id'];
        }
        return $itemIds;
    }

    /**
     * Unreference a tag from an entity
     */
    public function unreference(): array
    {
        $this->Entity->canOrExplode('write');

        $sql = 'DELETE FROM tags2entity WHERE tag_id = :tag_id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag_id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        // now check if another entity is referencing it, if not, remove it from the tags table
        $sql = 'SELECT tag_id FROM tags2entity WHERE tag_id = :tag_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag_id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $tags = $req->fetchAll();

        if (empty($tags)) {
            $this->destroy();
        }
        return $this->Entity->readOne();
    }

    /**
     * Get an array of tags starting with the query
     *
     * @return array the tag list filtered by the term for autocomplete
     */
    public function getList(string $term): array
    {
        $tagListArr = array();
        // limit to 20 entries
        $tagsArr = array_slice($this->readAll($term), 0, 20);

        foreach ($tagsArr as $tag) {
            $tagListArr[] = $tag['tag'];
        }
        return $tagListArr;
    }
}
