<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use PDO;

/**
 * When we want to check for something.
 */
class UsersHelper
{
    /** @var Db $db db connection */
    private $Db;

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Check if a user owns experiments
     * This is used to prevent changing the team of a user with experiments
     *
     * @param int $userid the user to check
     * @return bool
     */
    public function hasExperiments(int $userid): bool
    {
        $sql = 'SELECT COUNT(id) FROM experiments WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (bool) $req->fetchColumn();
    }

    /**
     * Get the team id where the user belong
     *
     * @param int $userid
     * @return array
     */
    public function getTeamsFromUserid(int $userid): array
    {
        $sql = 'SELECT DISTINCT teams.id, teams.name FROM teams
            CROSS JOIN users2teams ON (users2teams.users_id = :userid AND users2teams.teams_id = teams.id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            throw new ImproperActionException('Could not find a team for this user!');
        }
        return $res;
    }

    /**
     * Get teams id from a userid
     *
     * @param int $userid
     * @return array
     */
    public function getTeamsIdFromUserid(int $userid): array
    {
        $teams = $this->getTeamsFromUserid($userid);
        $teamsIdArr = array();
        foreach ($teams as $team) {
            $teamsIdArr[] = $team['id'];
        }
        return $teamsIdArr;
    }

    /**
     * Return the group int that will be assigned to a new user in a team
     * 1 = sysadmin if it's the first user ever
     * 2 = admin for first user in a team
     * 4 = normal user
     *
     * @param int $team
     * @return int
     */
    public function getGroup(int $team): int
    {
        if ($this->isFirstUser()) {
            return 1;
        }

        if ($this->isFirstUserInTeam($team)) {
            return 2;
        }
        return 4;
    }

    /**
     * Do we have users in the DB ?
     *
     * @return bool
     */
    private function isFirstUser(): bool
    {
        $sql = 'SELECT COUNT(*) AS usernb FROM users';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $test = $req->fetch();

        return $test['usernb'] === '0';
    }

    /**
     * Are we the first user to register in a team ?
     *
     * @param int $team
     * @return bool
     */
    private function isFirstUserInTeam(int $team): bool
    {
        $sql = 'SELECT COUNT(*) AS usernb FROM users
            CROSS JOIN users2teams ON (users2teams.users_id = users.userid)
            WHERE users2teams.teams_id = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        $this->Db->execute($req);
        $test = $req->fetch();

        return $test['usernb'] === '0';
    }
}
