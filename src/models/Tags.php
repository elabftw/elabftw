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
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CrudInterface;
use PDO;

/**
 * All about the tag
 */
class Tags implements CrudInterface
{
    /** @var AbstractEntity $Entity an instance of AbstractEntity */
    public $Entity;

    /** @var Db $Db SQL Database */
    protected $Db;

    /**
     * Constructor
     *
     * @param AbstractEntity $entity
     */
    public function __construct(AbstractEntity $entity)
    {
        $this->Db = Db::getConnection();
        $this->Entity = $entity;
    }

    /**
     * Create a tag
     *
     * @param string $tag
     * @return int
     */
    public function create(string $tag): int
    {
        $this->Entity->canOrExplode('write');
        $tag = $this->checkTag($tag);

        $insertSql2 = 'INSERT INTO tags2entity (item_id, item_type, tag_id) VALUES (:item_id, :item_type, :tag_id)';
        $insertReq2 = $this->Db->prepare($insertSql2);
        // check if the tag doesn't exist already for the team
        $sql = 'SELECT id FROM tags WHERE tag = :tag AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag', $tag);
        $req->bindParam(':team', $this->Entity->Users->userData['team'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        $tagId = (int) $req->fetchColumn();

        // tag doesn't exist already
        if ($req->rowCount() === 0) {
            $insertSql = 'INSERT INTO tags (team, tag) VALUES (:team, :tag)';
            $insertReq = $this->Db->prepare($insertSql);
            $insertReq->bindParam(':tag', $tag);
            $insertReq->bindParam(':team', $this->Entity->Users->userData['team'], PDO::PARAM_INT);
            if ($insertReq->execute() !== true) {
                throw new DatabaseErrorException('Error while executing SQL query.');
            }
            $tagId = $this->Db->lastInsertId();
        }
        // now reference it
        $insertReq2->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $insertReq2->bindParam(':item_type', $this->Entity->type);
        $insertReq2->bindParam(':tag_id', $tagId, PDO::PARAM_INT);

        if ($insertReq2->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        return $tagId;
    }

    /**
     * Read all the tags from team
     *
     * @param string|null $term The beginning of the input for tag autocomplete
     * @return array
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
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Copy the tags from one experiment/item to an other.
     *
     * @param int $newId The id of the new experiment/item that will receive the tags
     * @param bool $toExperiments convert to experiments type (when creating from tpl)
     * @return void
     */
    public function copyTags(int $newId, bool $toExperiments = false): void
    {
        $sql = 'SELECT tag_id FROM tags2entity WHERE item_id = :item_id AND item_type = :item_type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':item_type', $this->Entity->type);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
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
                if ($insertReq->execute() !== true) {
                    throw new DatabaseErrorException('Error while executing SQL query.');
                }
            }
        }
    }

    /**
     * Get an array of tags starting with the query ($term)
     *
     * @param string $term the beginning of the tag
     * @return array the tag list filtered by the term
     */
    public function getList(string $term): array
    {
        $tagListArr = array();
        $tagsArr = $this->readAll($term);

        foreach ($tagsArr as $tag) {
            $tagListArr[] = $tag['tag'];
        }
        return $tagListArr;
    }

    /**
     * Update a tag
     *
     * @param string $tag tag value
     * @param string $newtag new tag value
     * @return void
     */
    public function update(string $tag, string $newtag): void
    {
        $newtag = $this->checkTag($newtag);

        $sql = 'UPDATE tags SET tag = :newtag WHERE tag = :tag AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag', $tag);
        $req->bindParam(':newtag', $newtag);
        $req->bindParam(':team', $this->Entity->Users->userData['team'], PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * If we have the same tag (after correcting a typo),
     * remove the tags that are the same and reference only one
     *
     * @param string $tag the tag to dedup
     * @return int the number of duplicates removed
     */
    public function deduplicate(string $tag): int
    {
        $sql = 'SELECT * FROM tags WHERE tag = :tag AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag', $tag);
        $req->bindParam(':team', $this->Entity->Users->userData['team'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        $count = $req->rowCount();
        if ($count < 2) {
            return 0;
        }

        // ok we have several tags that are the same in the same team
        // we want to update the reference mentionning them for the original tag id
        $tags = $req->fetchAll();
        if ($tags === false) {
            return 0;
        }
        // the first tag we find is the one we keep
        $targetTagId = $tags[0]['id'];

        // skip the first tag because we want to keep it
        // array holding all the tags we want to see disappear
        $tagsToDelete = array_slice($tags, 1);

        foreach ($tagsToDelete as $tag) {
            $sql = 'UPDATE tags2entity SET tag_id = :target_tag_id WHERE tag_id = :tag_id';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':target_tag_id', $targetTagId, PDO::PARAM_INT);
            $req->bindParam(':tag_id', $tag['id'], PDO::PARAM_INT);
            if ($req->execute() !== true) {
                throw new DatabaseErrorException('Error while executing SQL query.');
            }
        }

        // now delete the duplicate tags from the tags table
        $sql = 'DELETE FROM tags WHERE id = :id';
        $req = $this->Db->prepare($sql);
        foreach ($tagsToDelete as $tag) {
            $req->bindParam(':id', $tag['id'], PDO::PARAM_INT);
            if ($req->execute() !== true) {
                throw new DatabaseErrorException('Error while executing SQL query.');
            }
        }

        return count($tagsToDelete);
    }

    /**
     * Unreference a tag from an entity
     *
     * @param int $tagId id of the tag
     * @return void
     */
    public function unreference(int $tagId): void
    {
        $sql = 'DELETE FROM tags2entity WHERE tag_id = :tag_id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag_id', $tagId, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        // now check if another entity is referencing it, if not, remove it from the tags table
        $sql = 'SELECT tag_id FROM tags2entity WHERE tag_id = :tag_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag_id', $tagId, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        $tags = $req->fetchAll();

        if (empty($tags)) {
            $this->destroy($tagId);
        }
    }

    /**
     * Destroy a tag completely. Unreference it from everywhere and then delete it
     *
     * @param int $tagId id of the tag
     * @return void
     */
    public function destroy(int $tagId): void
    {
        // first unreference the tag
        $sql = 'DELETE FROM tags2entity WHERE tag_id = :tag_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag_id', $tagId, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        // now delete it from the tags table
        $sql = 'DELETE FROM tags WHERE id = :tag_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tag_id', $tagId, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Destroy all the tags for an item ID
     * Here the tag are not destroyed because it might be nice to keep the tags in memory
     * even when nothing is referencing it. Admin can manage tags anyway if it needs to be destroyed.
     *
     * @return void
     */
    public function destroyAll(): void
    {
        $sql = 'DELETE FROM tags2entity WHERE item_id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Sanitize tag, we remove '\' because it fucks up the javascript if you have this in the tags
     * also remove | because we use this as separator for tags in SQL
     *
     * @param string $tag The tag to check
     * @return string
     */
    private function checkTag(string $tag): string
    {
        $tag = filter_var($tag, FILTER_SANITIZE_STRING);
        if ($tag === false) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 1));
        }
        $tag = \trim(str_replace(array('\\', '|'), array('', ' '), $tag));
        // empty tags are disallowed
        if ($tag === '') {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 1));
        }
        return $tag;
    }
}
