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
use PDO;

/**
 * When we want to check for something.
 */
class UsersHelper
{
    private Db $Db;

    private int $userid;

    public function __construct(int $userid)
    {
        $this->Db = Db::getConnection();
        $this->userid = $userid;
    }

    /**
     * Check if a user owns experiments
     * This is used to prevent changing the team of a user with experiments
     */
    public function hasExperiments(): bool
    {
        $sql = 'SELECT COUNT(id) FROM experiments WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (bool) $req->fetchColumn();
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
        if ($res === false) {
            throw new ImproperActionException('Could not find a team for this user!');
        }
        return $res;
    }

    /**
     * Get the permissions for a user (admin/sysadmin/can lock)
     *
     * @return array<string, string>
     */
    public function getPermissions(): array
    {
        $default = array('is_admin' => '0', 'is_sysadmin' => '0', 'can_lock' => '0');

        $sql = 'SELECT groups.is_sysadmin, groups.is_admin, groups.can_lock
            FROM `groups`
            CROSS JOIN users on (users.usergroup = groups.id) where users.userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch();
        if (!is_array($res)) {
            return $default;
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
}
