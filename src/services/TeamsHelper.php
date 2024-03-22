<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Usergroup;
use Elabftw\Exceptions\ResourceNotFoundException;
use PDO;

class TeamsHelper
{
    private Db $Db;

    public function __construct(private int $team)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Return the group int that will be assigned to a new user in a team
     * 1 = sysadmin if it's the first user ever
     * 2 = admin for first user in a team
     * 4 = normal user
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

    public function getPermissions(int $userid): array
    {
        $group = $this->getGroupInTeam($userid);
        $sql = 'SELECT `is_admin` FROM `groups` WHERE `id` = :group';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':group', $group, PDO::PARAM_INT);
        $this->Db->execute($req);

        try {
            return $this->Db->fetch($req);
        } catch (ResourceNotFoundException) {
            return array('is_admin' => 0);
        }
    }

    public function getUserInTeam(int $userid): array
    {
        $sql = 'SELECT `users_id`, `groups_id` FROM `users2teams` WHERE `teams_id` = :team AND `users_id` = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    public function isAdminInTeam(int $userid): bool
    {
        return $this->getUserInTeam($userid)['groups_id'] <= Usergroup::Admin->value;
    }

    /**
     * @deprecated
     */
    public function isUserInTeam(int $userid): bool
    {
        $sql = 'SELECT `users_id` FROM `users2teams` WHERE `teams_id` = :team AND `users_id` = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (bool) $req->fetchColumn();
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
     * @deprecated
     */
    private function getGroupInTeam(int $userid): int
    {
        $sql = 'SELECT `groups_id` FROM `users2teams` WHERE `teams_id` = :team AND `users_id` = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (int) $req->fetchColumn();
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
