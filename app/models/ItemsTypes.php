<?php
/**
 * \Elabftw\Elabftw\ItemsTypes
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \PDO;
use \Exception;

/**
 * The kind of items you can have in the database for a team
 */
class ItemsTypes extends Panel
{
    /** The PDO object */
    private $pdo;

    /**
     * Constructor
     *
     * @throws Exception if user is not admin
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    /**
     * Create an item type
     *
     * @param string $name New name
     * @param string $color hexadecimal color code
     * @param string $template html for new body
     * @param int $team team ID
     * @return bool true if sql success
     */
    public function create($name, $color, $template, $team)
    {
        if (!$this->isAdmin()) {
            throw new Exception('Only admin can access this!');
        }
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        if (strlen($name) < 1) {
            $name = 'Unnamed';
        }

        // we remove the # of the hexacode and sanitize string
        $color = filter_var(substr($color, 0, 6), FILTER_SANITIZE_STRING);
        $template = check_body($template);
        $sql = "INSERT INTO items_types(name, bgcolor, template, team) VALUES(:name, :bgcolor, :template, :team)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':bgcolor', $color);
        $req->bindParam(':template', $template);
        $req->bindParam(':team', $team, \PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * SQL to get all items type
     *
     * @param int $team team ID
     * @return array all the items types for the team
     */
    public function read($team)
    {
        $sql = "SELECT * from items_types WHERE team = :team ORDER BY ordering ASC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $team, \PDO::PARAM_INT);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Update an item type
     *
     * @param int $id The ID of the item type
     * @param string $name name
     * @param string $color hexadecimal color
     * @param string $template html for the body
     * @param int $team Team ID
     * @return bool true if sql success
     */
    public function update($id, $name, $color, $template, $team)
    {
        if (!$this->isAdmin()) {
            throw new Exception('Only admin can access this!');
        }
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $color = filter_var($color, FILTER_SANITIZE_STRING);
        $template = check_body($template);
        $sql = "UPDATE items_types SET
            name = :name,
            team = :team,
            bgcolor = :bgcolor,
            template = :template
            WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':bgcolor', $color);
        $req->bindParam(':template', $template);
        $req->bindParam(':team', $team, \PDO::PARAM_INT);
        $req->bindParam(':id', $id, \PDO::PARAM_INT);

        return $req->execute();
    }
}
