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
use PDO;

class TeamsHelper
{
    /** @var Db $Db db connection */
    private $Db;

    /** @var int $team */
    private $team;

    public function __construct(int $team)
    {
        $this->Db = Db::getConnection();
        $this->team = $team;
    }

    /**
     * Return the group int that will be assigned to a new user in a team
     * 1 = sysadmin if it's the first user ever
     * 2 = admin for first user in a team
     * 4 = normal user
     */
    public function getGroup(): int
    {
        if ($this->isFirstUser()) {
            return 1;
        }

        if ($this->isFirstUserInTeam()) {
            return 2;
        }
        return 4;
    }

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
     * Do we have users in the DB?
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
     * Are we the first user to register in a team?
     */
    private function isFirstUserInTeam(): bool
    {
        $sql = 'SELECT COUNT(*) AS usernb FROM users
            CROSS JOIN users2teams ON (users2teams.users_id = users.userid)
            WHERE users2teams.teams_id = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);
        $test = $req->fetch();

        return $test['usernb'] === '0';
    }
}
