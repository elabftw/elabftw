<?php
/**
 * \Elabftw\Elabftw\ItemsTypes
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

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
     * @throws Exception if user is not admin
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
     * @return bool true if sql success
     */
    public function create(string $name, string $color, int $bookable, string $template, ?int $team = null): bool
    {
        if ($team === null) {
            $team = $this->Users->userData['team'];
        }
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        if (\mb_strlen($name) < 1) {
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
        $req->bindParam(':team', $team);

        return $req->execute();
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
        $req->bindParam(':id', $this->id);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->execute();

        if ($req->rowCount() === 0) {
            throw new Exception(_('Nothing to show with this id'));
        }

        return $req->fetchColumn();
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
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->execute();

        return $req->fetchAll();
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
        $req->bindParam(':id', $id);
        $req->execute();

        return $req->fetchColumn();
    }

    /**
     * Update an item type
     *
     * @param int $id The ID of the item type
     * @param string $name name
     * @param string $color hexadecimal color
     * @param int $bookable
     * @param string $template html for the body
     * @return bool true if sql success
     */
    public function update(int $id, string $name, string $color, int $bookable, string $template): bool
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
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->bindParam(':id', $id);

        return $req->execute();
    }

    /**
     * Count all items of this type
     *
     * @param int $id of the type
     * @return int
     */
    protected function countItems(int $id): int
    {
        $sql = "SELECT COUNT(*) FROM items WHERE type = :type";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':type', $id);
        $req->execute();
        return (int) $req->fetchColumn();
    }

    /**
     * Destroy an item type
     *
     * @param int $id
     * @return bool
     */
    public function destroy(int $id): bool
    {
        // don't allow deletion of an item type with items
        if ($this->countItems($id) > 0) {
            throw new Exception(_("Remove all database items with this type before deleting this type."));
        }
        $sql = "DELETE FROM items_types WHERE id = :id AND team = :team";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':team', $this->Users->userData['team']);

        return $req->execute();
    }

    /**
     * Not implemented
     *
     */
    public function destroyAll(): bool
    {
        return false;
    }
}
