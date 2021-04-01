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
use Elabftw\Elabftw\ParamsProcessor;
use Elabftw\Interfaces\CreatableInterface;
use Elabftw\Interfaces\DestroyableInterface;
use Elabftw\Interfaces\ReadableInterface;
use PDO;

/**
 * All about the experiments links
 */
class Links implements CreatableInterface, ReadableInterface, DestroyableInterface
{
    public AbstractEntity $Entity;

    protected Db $Db;

    public function __construct(AbstractEntity $entity)
    {
        $this->Db = Db::getConnection();
        $this->Entity = $entity;
    }

    /**
     * Add a link to an experiment
     */
    public function create(ParamsProcessor $params): int
    {
        $link = $params->id;
        $Database = new Database($this->Entity->Users, $link);
        $Database->canOrExplode('read');
        $this->Entity->canOrExplode('write');

        // check if this link doesn't exist already
        $links = $this->read();
        foreach ($links as $existingLink) {
            if ((int) $existingLink['itemid'] === $link) {
                return 0;
            }
        }
        // create new link
        $sql = 'INSERT INTO ' . $this->Entity->type . '_links (item_id, link_id) VALUES(:item_id, :link_id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':link_id', $link, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Get links for an entity
     *
     * @return array links of the entity
     */
    public function read(): array
    {
        $sql = 'SELECT items.id AS itemid,
            ' . $this->Entity->type . '_links.id AS linkid,
            items.title,
            category.name,
            category.bookable,
            category.color
            FROM ' . $this->Entity->type . '_links
            LEFT JOIN items ON (' . $this->Entity->type . '_links.link_id = items.id)
            LEFT JOIN items_types AS category ON (items.category = category.id)
            WHERE ' . $this->Entity->type . '_links.item_id = :id
            ORDER by category.name ASC, items.title ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
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
            $sql = 'SELECT entity.id AS entityid, entity_links.id AS linkid, entity.title';

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
            $teamgroupsOfUser = $TeamGroups->getGroupsFromUser();
            if (!empty($teamgroupsOfUser)) {
                foreach ($teamgroupsOfUser as $teamgroup) {
                    $sql .= ' OR (entity.canread = ' . $teamgroup . ')';
                }
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
    public function duplicate(int $id, int $newId, $fromTpl = false): void
    {
        $table = $this->Entity->type;
        if ($fromTpl) {
            $table = 'experiments_templates';
        }
        $linksql = 'SELECT link_id FROM ' . $table . '_links WHERE item_id = :id';
        $linkreq = $this->Db->prepare($linksql);
        $linkreq->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($linkreq);

        while ($links = $linkreq->fetch()) {
            $sql = 'INSERT INTO ' . $this->Entity->type . '_links (link_id, item_id) VALUES(:link_id, :item_id)';
            $req = $this->Db->prepare($sql);
            $this->Db->execute($req, array(
                'link_id' => $links['link_id'],
                'item_id' => $newId,
            ));
        }
    }

    public function destroy(int $id): bool
    {
        $this->Entity->canOrExplode('write');
        $sql = 'DELETE FROM ' . $this->Entity->type . '_links WHERE id= :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
