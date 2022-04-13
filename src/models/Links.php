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

use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Traits\SetIdTrait;
use PDO;

/**
 * All about the experiments links
 */
class Links implements CrudInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(public AbstractEntity $Entity, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        // this field corresponds to the target id (link_id)
        $this->id = $id;
    }

    /**
     * Add a link to an experiment
     */
    public function create(ContentParamsInterface $params): int
    {
        $link = (int) $params->getContent();
        $Items = new Items($this->Entity->Users, $link);
        $Items->canOrExplode('read');
        $this->Entity->canOrExplode('write');

        $sql = 'INSERT INTO ' . $this->Entity->type . '_links (item_id, link_id) VALUES(:item_id, :link_id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':link_id', $link, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Get links for an entity
     */
    public function read(ContentParamsInterface $params): array
    {
        $sql = 'SELECT items.id AS itemid,
            items.title,
            category.name,
            category.bookable,
            category.color
            FROM ' . $this->Entity->type . '_links
            LEFT JOIN items ON (' . $this->Entity->type . '_links.link_id = items.id)
            LEFT JOIN items_types AS category ON (items.category = category.id)
            WHERE ' . $this->Entity->type . '_links.item_id = :id
            ORDER by category.name ASC, items.date ASC, items.title ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Get related entities
     *
     * @return array It contains two result arrays (items, experiments).
     * @phan-suppress PhanPluginPrintfVariableFormatString
     */
    public function readRelated(): array
    {
        $res = array('items' => array(), 'experiments' => array());

        foreach (array_keys($res) as $type) {
            $sql = 'SELECT entity.id AS entityid, entity.title';

            if ($type === 'items') {
                $sql .= ', category.name, category.bookable, category.color';
            }

            $sql .= ' FROM %1$s_links as entity_links
                LEFT JOIN %1$s AS entity ON (entity_links.item_id = entity.id)';

            if ($type === 'items') {
                $sql .= ' LEFT JOIN %1$s_types AS category ON (entity.category = category.id)';
            }

            // Only load entities from database for which the user has read permission.
            $sql .= " LEFT JOIN users ON (entity.userid = users.userid)
                CROSS JOIN users2teams ON (users2teams.users_id = users.userid
                                           AND users2teams.teams_id = :team_id)
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

            $sql .= ') ORDER by';

            if ($type === 'items') {
                $sql .= ' category.name ASC,';
            }

            $sql .= ' entity.title ASC';

            $req = $this->Db->prepare(sprintf($sql, $type));
            $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
            $req->bindParam(':user_id', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
            $req->bindParam(':team_id', $this->Entity->Users->userData['team'], PDO::PARAM_INT);

            $this->Db->execute($req);

            $partialRes = $req->fetchAll();
            if ($partialRes !== false) {
                $res[$type] = $partialRes;
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
    public function duplicate(int $id, int $newId, $fromTpl = false): bool
    {
        $table = $this->Entity->type;
        if ($fromTpl) {
            $table = $this->Entity instanceof Experiments ? 'experiments_templates' : 'items_types';
        }

        $sql = 'INSERT INTO ' . $this->Entity->type . '_links (item_id, link_id) SELECT :new_id, link_id FROM ' . $table . '_links WHERE item_id = :old_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':new_id', $newId, PDO::PARAM_INT);
        $req->bindParam(':old_id', $id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Copy the links of an item into our entity
     */
    public function import(): bool
    {
        $this->Entity->canOrExplode('write');

        // the :item_id of the SELECT will be the same for all rows: our current entity id
        $sql = 'INSERT INTO ' . $this->Entity->type . '_links (item_id, link_id) SELECT :item_id, link_id FROM items_links WHERE item_id = :link_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function update(ContentParamsInterface $params): bool
    {
        return false;
    }

    public function destroy(): bool
    {
        $this->Entity->canOrExplode('write');

        $sql = 'DELETE FROM ' . $this->Entity->type . '_links WHERE link_id = :link_id AND item_id = :item_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
