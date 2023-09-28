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
use Elabftw\Services\TeamsHelper;
use Elabftw\Traits\SetIdTrait;
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
        return sprintf('api/v2/%s/%d/tags/', $this->Entity->page, $this->Entity->id ?? 0);
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create(new TagParam($reqBody['tag']));
    }

    public function readOne(): array
    {
        return (new TeamTags($this->Entity->Users, $this->id))->readOne();
    }

    public function readAll(): array
    {
        $sql = sprintf(
            'SELECT DISTINCT tag, tags2%1$s.tags_id, (favtags2users.tags_id IS NOT NULL) AS is_favorite
                FROM tags2%1$s
                LEFT JOIN tags ON (tags2%1$s.tags_id = tags.id)
                LEFT JOIN favtags2users ON (favtags2users.users_id = :userid AND favtags2users.tags_id = tags.id)
                WHERE %1$s_id = :entity_id',
            $this->Entity->type,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
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
    public function copyTags(int $newId, bool $toExperiments = false): void
    {
        $tags = $this->readAll();
        $type = $this->Entity->type;
        // an experiment template transforms into an experiment
        if ($toExperiments) {
            $type = 'experiments';
        }

        $insertSql = sprintf('INSERT IGNORE INTO tags2%1$s (%1$s_id, tags_id) VALUES (:entity_id, :tags_id)', $type);
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
     * Create a tag
     */
    private function create(TagParam $params): int
    {
        $this->Entity->canOrExplode('write');

        $insertSql2 = sprintf(
            'INSERT IGNORE INTO tags2%1$s (%1$s_id, tags_id)
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
            $TeamsHelper = new TeamsHelper((int) $this->Entity->Users->userData['team']);
            if ($teamConfigArr['user_create_tag'] === 0 && $TeamsHelper->isAdminInTeam($this->Entity->Users->userData['userid']) === false) {
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
     * Unreference a tag from an entity, and possibly delete it if it's the last of its kind
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

        // tag is removed from tags table if no other entity is referencing it
        $selectArr = array_map(function (string $entityType): string {
            return 'SELECT tags_id FROM tags2' . $entityType . ' WHERE tags_id = :tags_id';
        }, EntityType::getAllValues());
        $sql = sprintf(
            'DELETE FROM tags WHERE id = :tag_id AND id NOT IN (%s)',
            implode(' UNION ', $selectArr),
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':tags_id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Entity->readOne();
    }
}
