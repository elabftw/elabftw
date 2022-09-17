<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

//use Elabftw\Factories\EntityFactory;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Traits\SetIdTrait;
use PDO;

/**
 * All about Links
 */
class Links implements RestInterface
{
    use SetIdTrait;

    protected Db $Db;

    protected array $categoryTables;

    public function __construct(public AbstractEntity $Entity, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        // this field corresponds to the target id (link_id)
        $this->id = $id;

        $this->categoryTables = array(
            $this->Entity::TYPE_ITEMS => 'items_types',
            $this->Entity::TYPE_EXPERIMENTS => 'status',
        );
    }

    public function getPage(): string
    {
        return $this->Entity->getPage();
    }

    public function patch(Action $action, array $params): array
    {
        return array();
    }

    /**
     * Get links for an entity
     *
     * @return array It contains two result arrays (items, experiments).
     */
    public function readAll(): array
    {
        $res = array(
            $this->Entity::TYPE_ITEMS => array(),
            $this->Entity::TYPE_EXPERIMENTS => array(),
        );

        foreach (array_map('strval', array_keys($res)) as $targetEntityType) {
            // Don't try to get links to experiments for templates
            if ($this->canNotLinkToExp($targetEntityType)) {
                continue;
            }

            $sql = 'SELECT entity.id AS itemid,
                entity.title,
                entity.elabid,
                category.title AS category,
                ' . ($targetEntityType === $this->Entity::TYPE_EXPERIMENTS ? '' : 'category.bookable,') . '
                category.color
                FROM ' . $this->getTableName($targetEntityType) . '
                LEFT JOIN ' . $targetEntityType . ' AS entity ON (' . $this->getTableName($targetEntityType) . '.link_id = entity.id)
                LEFT JOIN ' . $this->categoryTables[$targetEntityType] . ' AS category ON (entity.category = category.id)
                WHERE ' . $this->getTableName($targetEntityType) . '.item_id = :id
                ORDER by category.title ASC, entity.date ASC, entity.title ASC';

            $req = $this->Db->prepare($sql);
            $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
            $this->Db->execute($req);

            $partialRes = $req->fetchAll();
            if ($partialRes !== false) {
                $res[$targetEntityType] = $partialRes;
            }
        }

        return $res;
    }

    public function readOne(): array
    {
        return $this->readAll();
    }

    /**
     * Get related entities
     *
     * @return array It contains two result arrays (items, experiments).
     * @phan-suppress PhanPluginPrintfVariableFormatString
     */
    public function readRelated(): array
    {
        $res = array(
            $this->Entity::TYPE_ITEMS => array(),
            $this->Entity::TYPE_EXPERIMENTS => array(),
        );

        foreach (array_map('strval', array_keys($res)) as $targetEntityType) {
            $sql = 'SELECT entity.id AS entityid, entity.title';

            if ($targetEntityType === $this->Entity::TYPE_ITEMS) {
                $sql .= ', category.title, category.bookable, category.color';
            }

            $sql .= ' FROM ' . $this->getTableNameRelated($targetEntityType) . ' as entity_links
                LEFT JOIN ' . $targetEntityType . ' AS entity ON (entity_links.item_id = entity.id)';

            if ($targetEntityType === $this->Entity::TYPE_ITEMS) {
                $sql .= ' LEFT JOIN ' . $this->categoryTables[$targetEntityType] . ' AS category ON (entity.category = category.id)';
            }

            // Only load entities from database for which the user has read permission.
            $sql .= " LEFT JOIN users ON (entity.userid = users.userid)
                CROSS JOIN users2teams ON (
                    users2teams.users_id = users.userid
                    AND users2teams.teams_id = :team_id
                )
                WHERE entity_links.link_id = :id
                AND (entity.canread = 'public'
                     OR entity.canread = 'organization'
                     OR (entity.canread = 'team'
                         AND users2teams.users_id = entity.userid)
                     OR (entity.canread = 'user'
                         AND entity.userid = :user_id)";

            // add all the teamgroups in which the user is
            $TeamGroups = new TeamGroups($this->Entity->Users);
            $teamgroupsOfUser = array_column($TeamGroups->readGroupsFromUser(), 'id');
            foreach ($teamgroupsOfUser as $teamgroup) {
                $sql .= ' OR (entity.canread = ' . $teamgroup . ')';
            }

            $sql .= ') AND entity.state = ' . $this->Entity::STATE_NORMAL . ' ORDER by';

            if ($targetEntityType === $this->Entity::TYPE_ITEMS) {
                $sql .= ' category.title ASC,';
            }

            $sql .= ' entity.title ASC';

            $req = $this->Db->prepare($sql);
            $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
            $req->bindParam(':user_id', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
            $req->bindParam(':team_id', $this->Entity->Users->userData['team'], PDO::PARAM_INT);

            $this->Db->execute($req);

            $partialRes = $req->fetchAll();
            if ($partialRes !== false) {
                $res[$targetEntityType] = $partialRes;
            }
        }
        return $res;
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
        $res = array(
            $this->Entity::TYPE_ITEMS => true,
            $this->Entity::TYPE_EXPERIMENTS => true,
        );

        foreach (array_map('strval', array_keys($res)) as $targetEntityType) {
            // Don't try to get links to experiments for and from templates
            if ($this->canNotLinkToExp($targetEntityType, $fromTpl)) {
                continue;
            }

            $sql = 'INSERT INTO ' . $this->getTableName($targetEntityType) . ' (item_id, link_id)
                SELECT :new_id, link_id
                FROM ' . $this->getTableName($targetEntityType, fromTpl: $fromTpl) . '
                WHERE item_id = :old_id';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':new_id', $newId, PDO::PARAM_INT);
            $req->bindParam(':old_id', $id, PDO::PARAM_INT);

            $res[$targetEntityType] = $this->Db->execute($req);
        }

        //return $res[$this->Entity::TYPE_ITEMS] && $res[$this->Entity::TYPE_EXPERIMENTS];
        // TODO
        return 0;
    }

    public function update(): bool
    {
        return false;
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return match ($action) {
            Action::Create => $this->create($reqBody['targetEntityType']),
            Action::Duplicate => $this->import($reqBody['targetEntityType']),
            default => throw new ImproperActionException('Invalid action for links create.'),
        };
    }

    // make params parameter optional so we don't break the interface
    public function destroy(): bool
    {
        $this->Entity->canOrExplode('write');
        /*
        if ($params === null) {
            $params = new ContentParams(extra: array('targetEntity' => 'links'));
        }
         */

        // TODO FIXME
        $table = 'experiments_links';
        //$sql = 'DELETE FROM ' . $this->getTableName($params->getExtra('targetEntity')) . ' WHERE link_id = :link_id AND item_id = :item_id';
        $sql = 'DELETE FROM ' . $table . ' WHERE link_id = :link_id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    // links to experiments only exist for experiments and database items but not for any templates
    protected function canNotLinkToExp(string $targetEntityType, bool $fromTpl = false): bool
    {
        return (
            $targetEntityType === $this->Entity::TYPE_EXPERIMENTS
            && ($fromTpl || !($this->Entity instanceof AbstractConcreteEntity))
        );
    }

    protected function getTableName(string $targetEntityType, ?string $importEntityType = null, bool $fromTpl = false): string
    {
        if ($fromTpl) {
            return ($this->Entity instanceof Experiments || $this->Entity instanceof Templates)
                ? 'experiments_templates_links'
                : 'items_types_links';
        }

        if ($targetEntityType === $this->Entity::TYPE_EXPERIMENTS) {
            return ($importEntityType ?? $this->Entity->type) . '2' . $this->Entity::TYPE_EXPERIMENTS;
        }

        return ($importEntityType ?? $this->Entity->type) . '_links';
    }

    protected function getTableNameRelated(string $targetEntityType): string
    {
        if ($this->Entity instanceof Experiments) {
            return $targetEntityType . '2' . $this->Entity->type;
        }

        return $targetEntityType . '_links';
    }

    /**
     * Add a link to an entity
     * Links to Items are possible from all entities
     * Links to Experiments are only allowed from other Experiments and Items
     */
    private function create(string $targetEntityType): int
    {
        if (!($this->Entity instanceof AbstractConcreteEntity)) {
            throw new ImproperActionException('Links can only be created to experiments and database items.');
        }
        if ($this->canNotLinkToExp($targetEntityType)) {
            throw new ImproperActionException('Links to experiments can only be added to other experiments and database items.');
        }

        // use IGNORE to avoid failure due to a key constraint violations
        $sql = 'INSERT IGNORE INTO ' . $this->getTableName($targetEntityType) . ' (item_id, link_id) VALUES(:item_id, :link_id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);

        return (int) $this->Db->execute($req);
    }

    /**
     * Copy the links of an item into our entity
     * Also copy links of an experiment into our entity unless it is a template
     */
    private function import(string $targetEntityType): int
    {
        $this->Entity->canOrExplode('write');

        $res = array(
            $this->Entity::TYPE_ITEMS => true,
            $this->Entity::TYPE_EXPERIMENTS => true,
        );

        foreach (array_map('strval', array_keys($res)) as $entityType) {
            // Don't try to get links to experiments for templates
            if ($this->canNotLinkToExp($entityType)) {
                continue;
            }
            // the :item_id of the SELECT will be the same for all rows: our current entity id
            // use IGNORE to avoid failure due to a key constraint violations
            $sql = 'INSERT IGNORE INTO ' . $this->getTableName($entityType) . ' (item_id, link_id)
                SELECT :item_id, link_id
                FROM ' . $this->getTableName($entityType, $targetEntityType) . '
                WHERE item_id = :link_id';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
            $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);

            $res[$entityType] = $this->Db->execute($req);
        }

        // TODO
        //return $res[$this->Entity::TYPE_ITEMS] && $res[$this->Entity::TYPE_EXPERIMENTS];
        return 0;
    }
}
