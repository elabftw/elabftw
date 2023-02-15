<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\UsersHelper;
use PDO;

/**
 * Manage the link between users and teams
 */
class Users2Teams
{
    protected Db $Db;

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Add one user to one team
     */
    public function create(int $userid, int $teamid, bool $isAdmin = false, bool $isOwner = false): bool
    {
        // primary key will take care of ensuring there are no duplicate tuples
        $sql = 'INSERT IGNORE INTO users2teams (`users_id`, `teams_id`, `is_admin`, `is_owner`) VALUES (:userid, :team, :admin, :owner);';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $teamid, PDO::PARAM_INT);
        $req->bindValue(':admin', $isAdmin, PDO::PARAM_INT);
        $req->bindValue(':owner', $isOwner, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Add one user to n teams
     *
     * @param array<array-key, int> $teamIdArr this is the validated array of teams that exist
     */
    public function addUserToTeams(int $userid, array $teamIdArr, bool $isAdmin = false, bool $isOwner = false): void
    {
        foreach ($teamIdArr as $teamId) {
            $this->create($userid, (int) $teamId, $isAdmin, $isOwner);
        }
    }

    /**
     * Remove one user from a team
     */
    public function destroy(int $userid, int $teamid): bool
    {
        // make sure that the user is in more than one team before removing the team
        $UsersHelper = new UsersHelper($userid);
        if (count($UsersHelper->getTeamsFromUserid()) === 1) {
            throw new ImproperActionException('Cannot remove last team from user: users must belong to at least one team.');
        }
        $sql = 'DELETE FROM users2teams WHERE `users_id` = :userid AND `teams_id` = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $teamid, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Remove a user from teams
     *
     * @param array<array-key, int> $teamIdArr this is the validated array of teams that exist
     */
    public function rmUserFromTeams(int $userid, array $teamIdArr): void
    {
        foreach ($teamIdArr as $teamId) {
            $this->destroy($userid, (int) $teamId);
        }
    }

    public function getUsersFromTeam(int $teamId): array
    {
        $sql = "SELECT users.*, CONCAT(users.firstname, ' ', users.lastname) AS fullname,
                users2teams.*
            FROM users2teams
            LEFT JOIN users ON (users2teams.users_id = users.userid)
            WHERE users2teams.teams_id = :teamid
            ORDER BY fullname ASC";
        $req = $this->Db->prepare($sql);
        $req->bindValue(':teamid', $teamId, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function getTeamsOfUser(int $userId): array
    {
        $sql = 'SELECT teams.*, users2teams.*
            FROM users2teams
            LEFT JOIN teams ON (users2teams.teams_id = teams.id)
            WHERE users2teams.users_id = :userid
            ORDER BY teams.name ASC';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $userId, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }
}
