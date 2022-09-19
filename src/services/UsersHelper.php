<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use function array_column;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users;
use PDO;

/**
 * When we want to check for something.
 */
class UsersHelper
{
    private Db $Db;

    public function __construct(private int $userid)
    {
        $this->Db = Db::getConnection();
    }

    public function cannotBeDeleted(): bool
    {
        return $this->hasExperiments() || $this->hasItems() || $this->isSysadmin();
    }

    /**
     * Count all the experiments owned by a user
     */
    public function countExperiments(): int
    {
        $sql = 'SELECT COUNT(id) FROM experiments WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Count all the items owned by a user
     */
    public function countItems(): int
    {
        $sql = 'SELECT COUNT(id) FROM items WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Count all the timestamped experiments owned by a user
     */
    public function countTimestampedExperiments(): int
    {
        $sql = 'SELECT COUNT(id) FROM experiments WHERE userid = :userid AND timestamped = 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Get the team id where the user belong
     */
    public function getTeamsFromUserid(): array
    {
        $sql = 'SELECT DISTINCT teams.id, teams.name FROM teams
            CROSS JOIN users2teams ON (users2teams.users_id = :userid AND users2teams.teams_id = teams.id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if (empty($res)) {
            throw new ImproperActionException('Could not find a team for this user!');
        }
        return $res;
    }

    /**
     * Get teams id from a userid
     */
    public function getTeamsIdFromUserid(): array
    {
        return array_column($this->getTeamsFromUserid(), 'id');
    }

    /**
     * Get teams name from a userid
     */
    public function getTeamsNameFromUserid(): array
    {
        return array_column($this->getTeamsFromUserid(), 'name');
    }

    private function hasExperiments(): bool
    {
        return $this->countExperiments() > 0;
    }

    private function hasItems(): bool
    {
        return $this->countItems() > 0;
    }

    private function isSysadmin(): bool
    {
        $Users = new Users($this->userid);
        return $Users->userData['is_sysadmin'] === 1;
    }
}
