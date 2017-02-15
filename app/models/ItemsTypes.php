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

use PDO;
use Exception;

/**
 * The kind of items you can have in the database for a team
 */
class ItemsTypes extends Entity
{
    /** The PDO object */
    protected $pdo;

    /** instance of Users */
    public $Users;

    /**
     * Constructor
     *
     * @param Users $users
     * @param int|null $id
     * @throws Exception if user is not admin
     */
    public function __construct(Users $users, $id = null)
    {
        $this->pdo = Db::getConnection();
        $this->Users = $users;
        if (!is_null($id)) {
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
    public function create($name, $color, $bookable, $template, $team = null)
    {
        if (is_null($team)) {
            $team = $this->Users->userData['team'];
        }
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        if (strlen($name) < 1) {
            $name = 'Unnamed';
        }

        $color = filter_var(substr($color, 0, 6), FILTER_SANITIZE_STRING);
        $template = Tools::checkBody($template);
        $sql = "INSERT INTO items_types(name, color, bookable, template, team) VALUES(:name, :color, :bookable, :template, :team)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':color', $color);
        $req->bindParam(':bookable', $bookable, PDO::PARAM_INT);
        $req->bindParam(':template', $template);
        $req->bindParam(':team', $team);

        return $req->execute();
    }

    /**
     * Read from an id
     *
     * @return array
     */
    public function read()
    {
        $sql = "SELECT template FROM items_types WHERE id = :id AND team = :team";
        $req = $this->pdo->prepare($sql);
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
    public function readAll()
    {
        $sql = "SELECT items_types.id AS category_id,
            items_types.name AS category,
            items_types.color,
            items_types.bookable,
            items_types.template
            from items_types WHERE team = :team ORDER BY ordering ASC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->execute();

        return $req->fetchAll();
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
    public function update($id, $name, $color, $bookable, $template)
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
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':color', $color);
        $req->bindParam(':bookable', $bookable, PDO::PARAM_INT);
        $req->bindParam(':template', $template);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->bindParam(':id', $id, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Count all items of this type
     *
     * @param int $id of the type
     * @return int
     */
    private function countItems($id)
    {
        $sql = "SELECT COUNT(*) FROM items WHERE type = :type";
        $req = $this->pdo->prepare($sql);
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
    public function destroy($id)
    {
        // don't allow deletion of an item type with items
        if ($this->countItems($id) > 0) {
            throw new Exception(_("Remove all database items with this type before deleting this type."));
        }
        $sql = "DELETE FROM items_types WHERE id = :id AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':team', $this->Users->userData['team']);

        return $req->execute();
    }
}
