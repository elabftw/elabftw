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

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Usergroup;
use Elabftw\Exceptions\IllegalActionException;
use PDO;

final class TeamsHelper
{
    private Db $Db;

    public function __construct(private int $team)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Make sure that a team to which a user tries to add themselves to
     * exists and is currently one of those selected as visible by the sysadmin.
     */
    public function teamIsVisibleOrExplode(): void
    {
        $sql = 'SELECT id, visible FROM teams WHERE id = :team_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team_id', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);
        $team = $req->fetch();
        if ($team == false || $team['visible'] !== 1) {
            throw new IllegalActionException("There is no visible team with ID $this->team .");
        }
    }

    /**
     * Return the usergroup that will be assigned to a new user in a team
     * Sysadmin if it's the first user ever
     * Admin for first user in a team
     * Normal user
     */
    public function getGroup(): Usergroup
    {
        if ($this->isFirstUser()) {
            return Usergroup::Sysadmin;
        }

        if ($this->isFirstUserInTeam()) {
            return Usergroup::Admin;
        }
        return Usergroup::User;
    }

    public function isAdmin(int $userid): bool
    {
        // groups_id is either 2 (admin) or 4 (user)
        $sql = 'SELECT `groups_id` FROM `users2teams`
            WHERE `teams_id` = :team
                AND `users_id` = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchColumn() === Usergroup::Admin->value;
    }

    public function getUserInTeam(int $userid): array
    {
        $sql = 'SELECT `users_id`, `groups_id` FROM `users2teams` WHERE `teams_id` = :team AND `users_id` = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetch() ?: array();
    }

    public function isAdminInTeam(int $userid): bool
    {
        $userInTeam = $this->getUserInTeam($userid);
        return !empty($userInTeam) && ($userInTeam['groups_id'] <= Usergroup::Admin->value);
    }

    public function isUserInTeam(int $userid): bool
    {
        return !empty($this->getUserInTeam($userid));
    }

    // just get the id and name array
    public function getSimple(): array
    {
        $sql = 'SELECT id, name FROM teams WHERE id = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetch();
    }

    /**
     * Get all the userid of active admins of the team
     */
    public function getAllAdminsUserid(): array
    {
        $sql = sprintf(
            'SELECT users_id
                FROM users2teams
                LEFT JOIN users
                    ON (users2teams.users_id = users.userid)
                WHERE groups_id IN (%d, %d)
                    AND users.archived = 0
                    AND users2teams.teams_id = :team',
            Usergroup::Sysadmin->value,
            Usergroup::Admin->value,
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Are we the first user to register in a team?
     */
    public function isFirstUserInTeam(): bool
    {
        $sql = 'SELECT COUNT(userid) AS usernb FROM users
            CROSS JOIN users2teams ON (users2teams.users_id = users.userid)
            WHERE users2teams.teams_id = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);
        $test = $req->fetch();

        return $test['usernb'] === 0;
    }

    /**
     * Do we have users in the DB?
     */
    private function isFirstUser(): bool
    {
        $sql = 'SELECT COUNT(*) AS usernb FROM users';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $test = $req->fetch();

        return $test['usernb'] === 0;
    }
}
