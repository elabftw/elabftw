<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\TagParam;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Traits\SetIdTrait;
use function implode;
use PDO;
use function sprintf;

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

    public function readOne(): array
    {
        $TeamTags = new TeamTags($this->Entity->Users, $this->id);
        return $TeamTags->readOne();
    }

    public function readAll(): array
    {
        $sql = sprintf(
            'SELECT DISTINCT tag, tags2%1$s.tags_id
                FROM tags2%1$s
                LEFT JOIN tags ON (tags2%1$s.tags_id = tags.id)
                WHERE %1$s_id = :entity_id',
            $this->Entity->type,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
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
        $tags = $this->readAll();
        $type = $this->Entity->type;
        // an experiment template transforms into an experiment
        if ($toExperiments) {
            $type = 'experiments';
        }

        $insertSql = sprintf('INSERT INTO tags2%1$s (%1$s_id, tags_id) VALUES (:entity_id, :tags_id)', $type);
        $insertReq = $this->Db->prepare($insertSql);

        foreach ($tags as $tag) {
            $insertReq->bindParam(':entity_id', $newId, PDO::PARAM_INT);
            $insertReq->bindParam(':tags_id', $tag['tags_id'], PDO::PARAM_INT);
            $this->Db->execute($insertReq);
        }
    }

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
    public function destroy(): bool
    {
        $sql = sprintf('DELETE FROM tags2%1$s WHERE %1$s_id = :id', $this->Entity->type);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Get a list of entity id filtered by tags
     *
     * @param array<array-key, string> $tags tags from the query string
     */
    public function getIdFromTags(array $tags): array
    {
        $sql = 'SELECT id FROM tags WHERE tag IN ("' . implode('", "', $tags) . '")';
        $req = $this->Db->prepare($sql);
        $req->execute();
        $tagIds = $req->fetchAll(PDO::FETCH_COLUMN);
        if (empty($tagIds)) {
            return array();
        }

        // look for item ids that have all the tags not only one of them
        // note: you can't have a parameter for the IN clause
        // the HAVING COUNT is necessary to make an AND search between tags
        $sql = sprintf(
            'SELECT %1$s_id
                FROM tags2%1$s
                WHERE tags_id IN (' . implode(',', $tagIds) . ')
                GROUP BY %1$s_id
                HAVING COUNT(DISTINCT tags_id) = :count',
            $this->Entity->type,
        );
        $req = $this->Db->prepare($sql);
        // note: we count on the number of provided tags, not the result of the first query as the same tag can appear mutiple times (from different teams)
        $req->bindValue(':count', count($tags), PDO::PARAM_INT);
        $req->execute();
        return $req->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Create a tag
     */
    private function create(TagParam $params): int
    {
        $this->Entity->canOrExplode('write');

        $insertSql2 = sprintf(
            'INSERT INTO tags2%1$s (%1$s_id, tags_id)
                VALUES (:entity_id, :tags_id)',
            $this->Entity->type,
        );
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
        $insertReq2->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $insertReq2->bindParam(':tags_id', $tagId, PDO::PARAM_INT);
        $this->Db->execute($insertReq2);

        return $tagId;
    }

    /**
     * Unreference a tag from an entity
     */
    private function unreference(): array
    {
        $this->Entity->canOrExplode('write');

        $sql = sprintf(
            'DELETE FROM tags2%1$s
                WHERE tags_id = :tags_id AND %1$s_id = :entity_id',
            $this->Entity->type,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tags_id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        // now check if another entity is referencing it, if not, remove it from the tags table
        $sqlfragments = array_map(function (string $entityType): string {
            return 'SELECT tags_id FROM tags2' . $entityType . ' WHERE tags_id = :tags_id';
        }, EntityType::getAllValues());
        $sql = implode(' UNION ALL ', $sqlfragments);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tags_id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $tags = $req->fetchAll();

        if (empty($tags)) {
            (new TeamTags($this->Entity->Users, $this->id))->destroy();
        }
        return $this->Entity->readOne();
    }
}
