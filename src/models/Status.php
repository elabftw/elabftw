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
use PDO;

/**
 * Things related to status in admin panel
 */
class Status extends AbstractCategory
{
    public function __construct(Users $users)
    {
        $this->Users = $users;
        $this->Db = Db::getConnection();
    }

    public function create(ParamsProcessor $params, int $team = null): int
    {
        if ($team === null) {
            $team = $this->Users->userData['team'];
        }
        $sql = 'INSERT INTO status(name, color, team, is_timestampable, is_default)
            VALUES(:name, :color, :team, :is_timestampable, 0)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $params->name, PDO::PARAM_STR);
        $req->bindParam(':color', $params->color, PDO::PARAM_STR);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        $req->bindParam(':is_timestampable', $params->isTimestampable, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Create a default set of status for a new team
     *
     * @param int $team the new team id
     */
    public function createDefault(int $team): bool
    {
        return $this->create(
            new ParamsProcessor(
                array(
                    'name' => 'Running',
                    'color' => '#29AEB9',
                    'isTimestampable' => 0,
                    'isDefault' => 1,
                )
            ),
            $team
        ) &&
            $this->create(
                new ParamsProcessor(array(
                'name' => 'Success',
                'color' => '#54AA08',
                'isTimestampable' => 1,
                'isDefault' => 0,
                )),
                $team
            ) &&
            $this->create(
                new ParamsProcessor(array(
                'name' => 'Need to be redone',
                'color' => '#C0C0C0',
                'isTimestampable' => 1,
                'isDefault' => 0,
                )),
                $team
            ) &&
            $this->create(
                new ParamsProcessor(array(
                'name' => 'Fail',
                'color' => '#C24F3D',
                'isTimestampable' => 1,
                'isDefault' => 0,
                )),
                $team
            );
    }

    public function readAll(): array
    {
        return $this->read();
    }

    /**
     * SQL to get all status from team
     *
     * @return array All status from the team
     */
    public function read(): array
    {
        $sql = 'SELECT status.id AS category_id,
            status.name AS category,
            status.color,
            status.is_timestampable,
            status.is_default
            FROM status WHERE team = :team ORDER BY ordering ASC';
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
     * Get the color of a status
     *
     * @param int $id ID of the category
     * @return string
     */
    public function readColor(int $id): string
    {
        $sql = 'SELECT color FROM status WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchColumn();
        if ($res === false || $res === null) {
            return '00FF00';
        }
        return (string) $res;
    }

    /**
     * Returns if a status may be timestamped
     *
     * @param int $status ID of the status
     * @return bool true if status may be timestamped
     */
    public function isTimestampable(int $status): bool
    {
        $sql = 'SELECT is_timestampable FROM status WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $status, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (bool) $req->fetchColumn();
    }

    /**
     * Update a status
     */
    public function update(ParamsProcessor $params): string
    {
        // make sure there is only one default status
        if ($params->isDefault === 1) {
            $this->setDefaultFalse();
        }

        $sql = 'UPDATE status SET
            name = :name,
            color = :color,
            is_timestampable = :is_timestampable,
            is_default = :is_default
            WHERE id = :id AND team = :team';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $params->name, PDO::PARAM_STR);
        $req->bindParam(':color', $params->color, PDO::PARAM_STR);
        $req->bindParam(':is_timestampable', $params->isTimestampable, PDO::PARAM_INT);
        $req->bindParam(':is_default', $params->isDefault, PDO::PARAM_INT);
        $req->bindParam(':id', $params->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $params->name;
    }

    public function destroy(int $id): bool
    {
        // don't allow deletion of a status with experiments
        if ($this->countItems($id) > 0) {
            throw new ImproperActionException(_('Remove all experiments with this status before deleting this status.'));
        }

        $sql = 'DELETE FROM status WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Count all experiments with this status
     */
    protected function countItems(int $id): int
    {
        $sql = 'SELECT COUNT(*) FROM experiments WHERE category = :category';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':category', $id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (int) $req->fetchColumn();
    }

    /**
     * Remove all the default status for a team.
     * If we set true to is_default somewhere, it's best to remove all other default
     * in the team so we won't have two default status
     */
    private function setDefaultFalse(): void
    {
        $sql = 'UPDATE status SET is_default = 0 WHERE team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}
