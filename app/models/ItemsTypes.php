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
class ItemsTypes extends Admin
{
    /** The PDO object */
    private $pdo;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
        if (!$this->checkPermission()) {
            throw new Exception('Only admin can access this!');
        }
    }

    /**
     * Create an item type
     *
     */
    public function create($name, $color, $template, $team)
    {
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        if (strlen($name) < 1) {
            $name = 'Unnamed';
        }

        // we remove the # of the hexacode and sanitize string
        $color = filter_var(substr($color, 0, 6), FILTER_SANITIZE_STRING);
        $template = check_body($template);
        $sql = "INSERT INTO items_types(name, team, bgcolor, template) VALUES(:name, :team, :bgcolor, :template)";
        $req = $this->pdo->prepare($sql);
        return $req->execute(array(
            'name' => $name,
            'team' => $team,
            'bgcolor' => $color,
            'template' => $template
        ));
    }

    /**
     * SQL to get all items type
     *
     * @param int team id
     * @return array
     */
    public function read($team)
    {
        $sql = "SELECT * from items_types WHERE team = :team ORDER BY ordering ASC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $team, \PDO::PARAM_INT);
        $req->execute();
        return $req->fetchAll();
    }
}
