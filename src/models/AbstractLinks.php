<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Traits\SetIdTrait;
use PDO;

/**
 * All about Links
 */
abstract class AbstractLinks implements RestInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(public AbstractEntity $Entity, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        // this field corresponds to the target id (link_id)
        $this->id = $id;
    }

    public function getPage(): string
    {
        return sprintf('%s%d/%s/', $this->Entity->getPage(), $this->Entity->id ?? '', $this->getTable());
    }

    public function patch(Action $action, array $params): array
    {
        return array();
    }

    /**
     * Get links for an entity
     */
    public function readAll(): array
    {
        // main category table
        $sql = 'SELECT entity.id AS itemid,
            entity.title,
            entity.elabid,
            statust.title AS category,
            ' . ($this instanceof ItemsLinks ? 'entity.is_bookable,' : '') . '
            statust.color,
            entity.state AS link_state
            FROM ' . $this->getTable() . '
            LEFT JOIN ' . $this->getTargetType() . ' AS entity ON (' . $this->getTable() . '.link_id = entity.id)
            LEFT JOIN ' . $this->getCategoryTable() . ' AS statust ON (entity.status = statust.id)
            WHERE ' . $this->getTable() . '.item_id = :id AND (entity.state = :state OR entity.state = :statearchived)
            ORDER by statust.title ASC, entity.date ASC, entity.title ASC';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $req->bindValue(':statearchived', State::Archived->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readOne(): array
    {
        return $this->readAll();
    }

    /**
     * Get related entities
     */
    public function readRelated(): array
    {
        $sql = 'SELECT entity.id AS entityid, entity.title';

        if ($this instanceof ItemsLinks) {
            $sql .= ', category.title as category, entity.is_bookable, category.color';
        }

        $sql .= ' FROM ' . $this->getRelatedTable() . ' as entity_links
            LEFT JOIN ' . $this->getTargetType() . ' AS entity ON (entity_links.item_id = entity.id)';

        if ($this instanceof ItemsLinks) {
            $sql .= ' LEFT JOIN ' . $this->getCategoryTable() . ' AS category ON (entity.category = category.id)';
        }

        // Only load entities from database for which the user has read permission.
        $sql .= sprintf(
            " LEFT JOIN users ON (entity.userid = users.userid)
            CROSS JOIN users2teams ON (
                users2teams.users_id = users.userid
                AND users2teams.teams_id = :team_id
            )
            WHERE entity_links.link_id = :id
            AND (
                (JSON_EXTRACT(entity.canread, '$.base') = %d) OR
                (JSON_EXTRACT(entity.canread, '$.base') = %d) OR
                (JSON_EXTRACT(entity.canread, '$.base') = %d AND users2teams.users_id = entity.userid) OR
                (JSON_EXTRACT(entity.canread, '$.base') = %d AND entity.userid = :user_id) OR
                (JSON_EXTRACT(entity.canread, '$.base') = %d AND entity.userid = :user_id)",
            BasePermissions::Full->value,
            BasePermissions::Organization->value,
            BasePermissions::MyTeams->value,
            BasePermissions::User->value,
            BasePermissions::UserOnly->value,
        );

        // look for teamgroups
        $TeamGroups = new TeamGroups($this->Entity->Users);
        $teamgroupsOfUser = array_column($TeamGroups->readGroupsFromUser(), 'id');
        if (!empty($teamgroupsOfUser)) {
            foreach ($teamgroupsOfUser as $teamgroup) {
                $sql .= sprintf(' OR (%d MEMBER OF (entity.canread->>"$.teamgroups"))', $teamgroup);
            }
        }

        // look for our userid in users part of the json
        $sql .= ' OR (:user_id MEMBER OF (entity.canread->>"$.users"))';

        $sql .= sprintf(') AND entity.state = %d ORDER by', State::Normal->value);

        if ($this instanceof ItemsLinks) {
            $sql .= ' category.title ASC,';
        }

        $sql .= ' entity.title ASC';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':user_id', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team_id', $this->Entity->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    /**
     * Copy the links from one entity to an other
     *
     * @param int $id The id of the original entity
     * @param int $newId The id of the new entity that will receive the links
     * @param bool $fromTpl do we duplicate from template?
     */
    public function duplicate(int $id, int $newId, $fromTpl = false): int
    {
        $table = $this->getTable();
        if ($fromTpl) {
            $table = $this->getTemplateTable();
        }
        $sql = 'INSERT INTO ' . $this->getTable() . ' (item_id, link_id)
            SELECT :new_id, link_id
            FROM ' . $table . '
            WHERE item_id = :old_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':new_id', $newId, PDO::PARAM_INT);
        $req->bindParam(':old_id', $id, PDO::PARAM_INT);

        return (int) $this->Db->execute($req);
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return match ($action) {
            Action::Create => $this->create(),
            Action::Duplicate => $this->import(),
            default => throw new ImproperActionException('Invalid action for links create.'),
        };
    }

    public function destroy(): bool
    {
        $this->Entity->canOrExplode('write');
        $this->Entity->touch();

        $sql = 'DELETE FROM ' . $this->getTable() . ' WHERE link_id = :link_id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    abstract protected function getTargetType(): string;

    abstract protected function getCategoryTable(): string;

    abstract protected function getStatusTable(): string;

    abstract protected function getTable(): string;

    abstract protected function getRelatedTable(): string;

    abstract protected function getTemplateTable(): string;

    abstract protected function getImportTargetTable(): string;

    /**
     * Add a link to an entity
     * Links to Items are possible from all entities
     * Links to Experiments are only allowed from other Experiments and Items
     */
    protected function create(): int
    {
        // don't insert a link to the same entity, make sure we check for the type too
        if ($this->Entity->id === $this->id && $this->Entity->type === $this->getTargetType()) {
            return 0;
        }
        $this->Entity->touch();

        // use IGNORE to avoid failure due to a key constraint violations
        $sql = 'INSERT IGNORE INTO ' . $this->getTable() . ' (item_id, link_id) VALUES(:item_id, :link_id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);

        $this->Db->execute($req);

        return $this->id;
    }

    /**
     * Copy the links of an item into our entity
     * Also copy links of an experiment into our entity unless it is a template
     */
    private function import(): int
    {
        $this->Entity->canOrExplode('write');

        // the :item_id of the SELECT will be the same for all rows: our current entity id
        // use IGNORE to avoid failure due to a key constraint violations
        $sql = 'INSERT IGNORE INTO ' . $this->getTable() . ' (item_id, link_id)
            SELECT :item_id, link_id
            FROM ' . $this->getImportTargetTable() . '
            WHERE item_id = :link_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);

        return (int) $this->Db->execute($req);
    }
}
