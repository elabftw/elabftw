<?php
/**
 * \Elabftw\Elabftw\Status
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
 * Things related to status in admin panel
 */
class Status extends AbstractCategory
{
    use EntityTrait;

    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var Users $Users instance of Users */
    public $Users;

    /**
     * Constructor
     *
     * @param Users $users
     */
    public function __construct(Users $users)
    {
        $this->Users = $users;
        $this->Db = Db::getConnection();
    }

    /**
     * Create a new status
     *
     * @param string $name
     * @param string $color
     * @param int $isTimestampable
     * @param int $default
     * @param int|null $team
     * @return string id of the new item
     */
    public function create($name, $color, $isTimestampable = 1, $default = 0, $team = null)
    {
        if (is_null($team)) {
            $team = $this->Users->userData['team'];
        }
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        // we remove the # of the hexacode and sanitize string
        $color = filter_var(substr($color, 0, 6), FILTER_SANITIZE_STRING);

        if (strlen($name) < 1) {
            $name = 'Unnamed';
        }

        $sql = "INSERT INTO status(name, color, team, is_timestampable, is_default)
            VALUES(:name, :color, :team, :is_timestampable, :is_default)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':color', $color);
        $req->bindParam(':team', $team);
        $req->bindParam(':is_timestampable', $isTimestampable);
        $req->bindValue(':is_default', $default);

        $req->execute();

        return $this->Db->lastInsertId();
    }

    /**
     * Create a default set of status for a new team
     *
     * @param int $team the new team id
     * @return bool
     */
    public function createDefault($team)
    {
        return $this->create('Running', '29AEB9', 0, 1, $team) &&
            $this->create('Success', '54AA08', 1, 0, $team) &&
            $this->create('Need to be redone', 'C0C0C0', 1, 0, $team) &&
            $this->create('Fail', 'C24F3D', 1, 0, $team);
    }

    /**
     * SQL to get all status from team
     *
     * @return array All status from the team
     */
    public function readAll()
    {
        $sql = "SELECT status.id AS category_id,
            status.name AS category,
            status.color,
            status.is_timestampable,
            status.is_default
            FROM status WHERE team = :team ORDER BY ordering ASC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Get the color of a status
     *
     * @param int $id ID of the category
     * @return string
     */
    public function readColor($id)
    {
        $sql = "SELECT color FROM status WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->execute();

        return $req->fetchColumn();
    }

    /**
     * Returns if a status may be timestamped
     *
     * @param int $status ID of the status
     * @return bool true if status may be timestamped
     */
    public function isTimestampable($status)
    {
         $sql = "SELECT is_timestampable FROM status WHERE id = :id";
         $req = $this->Db->prepare($sql);
         $req->bindParam(':id', $status, PDO::PARAM_INT);
         $req->execute();

         return (bool) $req->fetchColumn();
    }

    /**
     * Remove all the default status for a team.
     * If we set true to is_default somewhere, it's best to remove all other default
     * in the team so we won't have two default status
     *
     * @return bool true if sql success
     */
    private function setDefaultFalse()
    {
        $sql = "UPDATE status SET is_default = 0 WHERE team = :team";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team']);

        return $req->execute();
    }

    /**
     * Update a status
     *
     * @param int $id ID of the status
     * @param string $name New name
     * @param string $color New color
     * @param int $isTimestampable May this status be timestamped
     * @param bool $isDefault
     * @return bool true if sql success
     */
    public function update($id, $name, $color, $isTimestampable, $isDefault)
    {
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $color = filter_var($color, FILTER_SANITIZE_STRING);

        if (($isDefault != 'false') && $this->setDefaultFalse()) {
            $default = 1;
        } else {
            $default = 0;
        }

        $sql = "UPDATE status SET
            name = :name,
            color = :color,
            is_timestampable = :is_timestampable,
            is_default = :is_default
            WHERE id = :id AND team = :team";

        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':color', $color);
        $req->bindParam(':is_timestampable', $isTimestampable);
        $req->bindParam(':is_default', $default);
        $req->bindParam(':id', $id);
        $req->bindParam(':team', $this->Users->userData['team']);

        return $req->execute();
    }

    /**
     * Count all experiments with this status
     *
     * @param int $id
     * @return int
     */
    protected function countItems($id)
    {
        $sql = "SELECT COUNT(*) FROM experiments WHERE status = :status";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':status', $id, PDO::PARAM_INT);
        $req->execute();

        return (int) $req->fetchColumn();
    }

    /**
     * Destroy a status
     *
     * @param int $id id of the status
     * @return bool
     */
    public function destroy($id)
    {
        // don't allow deletion of a status with experiments
        if ($this->countItems($id) > 0) {
            throw new Exception(_("Remove all experiments with this status before deleting this status."));
        }

        $sql = "DELETE FROM status WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id);

        return $req->execute();
    }

    /**
     * Not implemented
     *
     */
    public function destroyAll()
    {
    }
}
