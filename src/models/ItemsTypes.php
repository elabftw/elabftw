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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Traits\EntityTrait;
use PDO;

/**
 * The kind of items you can have in the database for a team
 */
class ItemsTypes extends AbstractCategory
{
    use EntityTrait;

    public function __construct(Users $users, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->Users = $users;
        if ($id !== null) {
            $this->setId($id);
        }
    }

    public function create(ParamsProcessor $params, ?int $team = null): int
    {
        if ($team === null) {
            $team = $this->Users->userData['team'];
        }

        $sql = 'INSERT INTO items_types(name, color, bookable, template, team)
            VALUES(:name, :color, :bookable, :template, :team)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $params->name, PDO::PARAM_STR);
        $req->bindParam(':color', $params->color, PDO::PARAM_STR);
        $req->bindParam(':bookable', $params->bookable, PDO::PARAM_INT);
        $req->bindParam(':template', $params->template, PDO::PARAM_STR);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Read the body (template) of the item_type from an id
     */
    public function read(): array
    {
        $sql = 'SELECT template FROM items_types WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        if ($req->rowCount() === 0) {
            throw new ImproperActionException(_('Nothing to show with this id'));
        }

        $res = $req->fetch();
        if ($res === false || $res === null) {
            return array();
        }
        return $res;
    }

    /**
     * SQL to get all items type
     *
     * @return array all the items types for the team
     */
    public function readAll(): array
    {
        $sql = 'SELECT items_types.id AS category_id,
            items_types.name AS category,
            items_types.color,
            items_types.bookable,
            items_types.template,
            items_types.ordering
            FROM items_types WHERE team = :team ORDER BY ordering ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Get the color of an item type
     *
     * @param int $id ID of the category
     */
    public function readColor(int $id): string
    {
        $sql = 'SELECT color FROM items_types WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchColumn();
        if ($res === false || $res === null) {
            return '';
        }
        return (string) $res;
    }

    /**
     * Update an item type
     */
    public function update(ParamsProcessor $params): string
    {
        $sql = 'UPDATE items_types SET
            name = :name,
            team = :team,
            color = :color,
            bookable = :bookable,
            template = :template
            WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $params->name, PDO::PARAM_STR);
        $req->bindParam(':color', $params->color, PDO::PARAM_STR);
        $req->bindParam(':bookable', $params->bookable, PDO::PARAM_INT);
        $req->bindParam(':template', $params->template, PDO::PARAM_STR);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $params->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $params->template;
    }

    /**
     * Destroy an item type
     *
     */
    public function destroy(int $id): bool
    {
        // don't allow deletion of an item type with items
        if ($this->countItems($id) > 0) {
            throw new ImproperActionException(_('Remove all database items with this type before deleting this type.'));
        }
        $sql = 'DELETE FROM items_types WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Count all items of this type
     *
     * @param int $id of the type
     * @return int
     */
    protected function countItems(int $id): int
    {
        $sql = 'SELECT COUNT(*) FROM items WHERE category = :category';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':category', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }
}
