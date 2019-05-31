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

        $sql = 'INSERT INTO ' . $this->Entity->type . '_links (item_id, link_id) VALUES(:item_id, :link_id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':link_id', $link, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Get links for an entity
     *
     * @return array links of the entity
     */
    public function readAll(): array
    {
        $this->Entity->canOrExplode('read');

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
        $req->bindParam(':id', $this->Entity->id, PDO::PARAM_INT);
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
        if ($linkreq->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        while ($links = $linkreq->fetch()) {
            $sql = 'INSERT INTO ' . $this->Entity->type . '_links (link_id, item_id) VALUES(:link_id, :item_id)';
            $req = $this->Db->prepare($sql);
            $req->execute(array(
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

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }
}
