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
use PDO;

class TeamsHelper
{
    private Db $Db;

    public function __construct(private int $team)
    {
        $this->Db = Db::getConnection();
    }

    public function isUserInTeam(int $userid): bool
    {
        $sql = 'SELECT users_id FROM users2teams WHERE teams_id = :team AND users_id = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (bool) $req->fetchColumn();
    }

    public function getAllAdminsUserid(): array
    {
        $sql = 'SELECT userid FROM users
            CROSS JOIN users2teams ON (
                users2teams.users_id = users.userid
                AND users2teams.teams_id = :team
                AND users2teams.is_admin = 1
            )
            WHERE validated = 1
                AND archived = 0';
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
    public function isFirstUser(): bool
    {
        $sql = 'SELECT COUNT(*) AS usernb FROM users';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $test = $req->fetch();

        return $test['usernb'] === 0;
    }
}
