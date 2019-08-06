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
use Elabftw\Exceptions\DatabaseErrorException;
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
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        return (bool) $req->fetchColumn();
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
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
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
        $sql = 'SELECT COUNT(*) AS usernb FROM users WHERE team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        $test = $req->fetch();

        return $test['usernb'] === '0';
    }
}
