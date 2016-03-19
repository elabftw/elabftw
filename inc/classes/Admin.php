<?php
/**
 * \Elabftw\Elabftw\Admin
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
 * Stuff for admin panel
 */
class Admin
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
    }

    /**
     * Only admin can use this
     *
     * @return int 1 if is_admin
     */
    protected function checkPermission()
    {
        return $_SESSION['is_admin'];
    }

    /**
     * SQL to get all items type
     *
     * @param int team id
     * @return array
     */
    public function itemsTypesRead($team)
    {
        $sql = "SELECT * from items_types WHERE team = :team ORDER BY ordering ASC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $team, \PDO::PARAM_INT);
        $req->execute();
        return $req->fetchAll();
    }
}
