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
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Traits\EntityTrait;
use PDO;

/**
 * The kind of items you can have in the database for a team
 */
class ItemsTypes extends AbstractCategory
{
    use EntityTrait;

    /**
     * Constructor
     *
     * @param Users $users
     * @param int|null $id
     */
    public function __construct(Users $users, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->Users = $users;
        if ($id !== null) {
            $this->setId($id);
        }
    }

    /**
     * Create an item type
     *
     * @param string $name New name
     * @param string $color hexadecimal color code
     * @param int $bookable
     * @param string $template html for new body
     * @param int|null $team
     * @return void
     */
    public function create(string $name, string $color, int $bookable, string $template, ?int $team = null): void
    {
        if ($team === null) {
            $team = $this->Users->userData['team'];
        }

        $name = filter_var($name, FILTER_SANITIZE_STRING);
        if ($name === '') {
            $name = 'Unnamed';
        }
        $color = filter_var(substr($color, 0, 6), FILTER_SANITIZE_STRING);
        $template = Tools::checkBody($template);

        $sql = "INSERT INTO items_types(name, color, bookable, template, team)
            VALUES(:name, :color, :bookable, :template, :team)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':color', $color);
        $req->bindParam(':bookable', $bookable);
        $req->bindParam(':template', $template);
        $req->bindParam(':team', $team, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Read the body (template) of the item_type from an id
     *
     * @return string
     */
    public function read(): string
    {
        $sql = "SELECT template FROM items_types WHERE id = :id AND team = :team";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        if ($req->rowCount() === 0) {
            throw new ImproperActionException(_('Nothing to show with this id'));
        }

        $res = $req->fetchColumn();
        if ($res === false) {
            return '';
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
        $sql = "SELECT items_types.id AS category_id,
            items_types.name AS category,
            items_types.color,
            items_types.bookable,
            items_types.template,
            items_types.ordering
            from items_types WHERE team = :team ORDER BY ordering ASC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
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
     * Get the color of an item type
     *
     * @param int $id ID of the category
     * @return string
     */
    public function readColor(int $id): string
    {
        $sql = "SELECT color FROM items_types WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $res = $req->fetchColumn();
        if ($res === false) {
            return '';
        }
        return $res;
    }

    /**
     * Update an item type
     *
     * @param int $id The ID of the item type
     * @param string $name name
     * @param string $color hexadecimal color
     * @param int $bookable
     * @param string $template html for the body
     * @return void
     */
    public function update(int $id, string $name, string $color, int $bookable, string $template): void
    {
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $color = filter_var($color, FILTER_SANITIZE_STRING);
        $template = Tools::checkBody($template);
        $sql = "UPDATE items_types SET
            name = :name,
            team = :team,
            color = :color,
            bookable = :bookable,
            template = :template
            WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':color', $color);
        $req->bindParam(':bookable', $bookable);
        $req->bindParam(':template', $template);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Count all items of this type
     *
     * @param int $id of the type
     * @return int
     */
    protected function countItems(int $id): int
    {
        $sql = "SELECT COUNT(*) FROM items WHERE category = :category";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':category', $id, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        return (int) $req->fetchColumn();
    }

    /**
     * Destroy an item type
     *
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        // don't allow deletion of an item type with items
        if ($this->countItems($id) > 0) {
            throw new ImproperActionException(_("Remove all database items with this type before deleting this type."));
        }
        $sql = "DELETE FROM items_types WHERE id = :id AND team = :team";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Not implemented
     *
     * @return void
     */
    public function destroyAll(): void
    {
        return;
    }
}
