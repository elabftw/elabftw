<?php
/**
 * \Elabftw\Elabftw\Status
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;
use Exception;

/**
 * Things related to status in admin panel
 */
class Status extends Panel
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
     * Create a new status
     *
     * @param string $name
     * @param string $color
     * @param int $team
     * @return bool true if sql success
     */
    public function create($name, $color, $team)
    {
        if (!$this->isAdmin()) {
            throw new Exception('Only admin can access this!');
        }
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        // we remove the # of the hexacode and sanitize string
        $color = filter_var(substr($color, 0, 6), FILTER_SANITIZE_STRING);

        if (strlen($name) < 1) {
            $name = 'Unnamed';
        }

        $sql = "INSERT INTO status(name, color, team, is_default) VALUES(:name, :color, :team, :is_default)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':color', $color);
        $req->bindParam(':team', $team, \PDO::PARAM_INT);
        $req->bindValue(':is_default', 0);

        return $req->execute();
    }

    /**
     * SQL to get all status from team
     *
     * @param int team id
     * @return array All status from the team
     */
    public function read($team)
    {
        $sql = "SELECT * FROM status WHERE team = :team ORDER BY ordering ASC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $team, \PDO::PARAM_INT);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Get the color of a status
     *
     * @param int $status ID of the status
     * @return string
     */
    public function readColor($status)
    {
        $sql = "SELECT color FROM status WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $status, \PDO::PARAM_INT);
        $req->execute();

        return $req->fetchColumn();
    }

    /**
     * Remove all the default status for a team.
     * If we set true to is_default somewhere, it's best to remove all other default
     * in the team so we won't have two default status
     *
     * @param int $team Team ID
     * @return bool true if sql success
     */
    public function setDefaultFalse($team)
    {
        if (!$this->isAdmin()) {
            throw new Exception('Only admin can access this!');
        }
        $sql = "UPDATE status SET is_default = 0 WHERE team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $team, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Update a status
     *
     * @param int $id ID of the status
     * @param string $name New name
     * @param string $color New color
     * @param string $defaultBox
     * @param int $team
     * @return bool true if sql success
     */
    public function update($id, $name, $color, $defaultBox, $team)
    {
        if (!$this->isAdmin()) {
            throw new Exception('Only admin can access this!');
        }
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $color = filter_var($color, FILTER_SANITIZE_STRING);

        if ($defaultBox && $this->setDefaultFalse($team)) {
            $default = 1;
        } else {
            $default = 0;
        }

        $sql = "UPDATE status SET
            name = :name,
            color = :color,
            is_default = :is_default
            WHERE id = :id AND team = :team";

        $req = $this->pdo->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':color', $color);
        $req->bindParam(':is_default', $default, \PDO::PARAM_INT);
        $req->bindParam(':id', $id, \PDO::PARAM_INT);
        $req->bindParam(':team', $team, \PDO::PARAM_INT);

        return $req->execute();
    }
}
