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
use Elabftw\Interfaces\CrudInterface;
use PDO;

/**
 * All about the experiments links
 */
class Links implements CrudInterface
{
    /** @var AbstractEntity $Entity instance of Experiments */
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
     * Add a link to an experiment
     *
     * @param int $link ID of database item
     * @return void
     */
    public function create(int $link): void
    {
        $Database = new Database($this->Entity->Users, $link);
        $Database->canOrExplode('read');
        $this->Entity->canOrExplode('write');

        // check if this link doesn't exist already
        $links = $this->readAll();
        foreach ($links as $existingLink) {
            if ((int) $existingLink['itemid'] === $link) {
                return;
            }
        }
        // create new link
        $sql = 'INSERT INTO ' . $this->Entity->type . '_links (item_id, link_id) VALUES(:item_id, :link_id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':link_id', $link, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Get links for an entity
     *
     * @return array links of the entity
     */
    public function readAll(): array
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
     * Get related items
     *
     * @return array
     */
    public function readRelatedItemsAll(): array
    {
        $sql = "SELECT items.id AS itemid,
            items_links.id AS linkid,
            items.title,
            category.name,
            category.bookable,
            category.color
            FROM items_links
            LEFT JOIN items ON (items_links.item_id = items.id)
            LEFT JOIN items_types AS category ON (items.category = category.id)
            LEFT JOIN users ON (items.userid = users.userid)
            CROSS JOIN users2teams ON (users2teams.users_id = users.userid
                                       AND users2teams.teams_id = :team_id)
            WHERE items_links.link_id = :id
            AND (items.canread = 'public'
                 OR items.canread = 'organization'
                 OR (items.canread = 'team'
                     AND users2teams.users_id = items.userid)
                 OR (items.canread = 'user'
                     AND items.userid = :user_id)";

        // add all the teamgroups in which the user is
        $TeamGroups = new TeamGroups($this->Entity->Users);
        $teamgroupsOfUser = $TeamGroups->getGroupsFromUser();
        if (!empty($teamgroupsOfUser)) {
            foreach ($teamgroupsOfUser as $teamgroup) {
                $sql .= " OR (items.canread = $teamgroup)";
            }
        }

        $sql .= ')
            ORDER by category.name ASC, items.title ASC';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':user_id', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team_id', $this->Entity->Users->userData['team'], PDO::PARAM_INT);

        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Get related experiments
     *
     * @return array
     */
    public function readRelatedExperimentsAll(): array
    {
        $sql = "SELECT experiments.id AS experimentid,
            experiments_links.id AS linkid,
            experiments.title
            FROM experiments_links
            LEFT JOIN experiments ON (experiments_links.item_id = experiments.id)
            LEFT JOIN users ON (experiments.userid = users.userid)
            CROSS JOIN users2teams ON (users2teams.users_id = users.userid
                                       AND users2teams.teams_id = :team_id)
            WHERE experiments_links.link_id = :id
            AND (experiments.canread = 'public'
                 OR experiments.canread = 'organization'
                 OR (experiments.canread = 'team'
                     AND users2teams.users_id = experiments.userid)
                 OR (experiments.canread = 'user'
                     AND experiments.userid = :user_id)";

        // add all the teamgroups in which the user is
        $TeamGroups = new TeamGroups($this->Entity->Users);
        $teamgroupsOfUser = $TeamGroups->getGroupsFromUser();
        if (!empty($teamgroupsOfUser)) {
            foreach ($teamgroupsOfUser as $teamgroup) {
                $sql .= " OR (experiments.canread = $teamgroup)";
            }
        }

        $sql .= ')
            ORDER by experiments.title ASC';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':user_id', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':team_id', $this->Entity->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Get links from an id
     *
     * @param int $id
     * @return array
     */
    public function readFromId(int $id): array
    {
        $sql = 'SELECT items.id AS itemid,
            ' . $this->Entity->type . '_links.id AS linkid,
            items.title,
            items_types.name,
            items_types.color
            FROM ' . $this->Entity->type . '_links
            LEFT JOIN items ON (' . $this->Entity->type . '_links.link_id = items.id)
            LEFT JOIN items_types ON (items.category = items_types.id)
            WHERE ' . $this->Entity->type . '_links.item_id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Copy the links from one entity to an other
     *
     * @param int $id The id of the original entity
     * @param int $newId The id of the new entity that will receive the links
     * @param bool $fromTpl do we duplicate from template?
     * @return void
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

    /**
     * Delete a link
     *
     * @param int $id ID of our link
     * @return void
     */
    public function destroy(int $id): void
    {
        $this->Entity->canOrExplode('write');

        $sql = 'DELETE FROM ' . $this->Entity->type . '_links WHERE id= :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}
