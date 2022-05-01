<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\ContentParamsInterface;
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
    public function create(ContentParamsInterface $params): bool
    {
        // primary key will take care of ensuring there are no duplicate tuples
        $sql = 'INSERT INTO users2teams (`users_id`, `teams_id`) VALUES (:userid, :team);';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', (int) $params->getExtra('userid'), PDO::PARAM_INT);
        $req->bindValue(':team', (int) $params->getExtra('teamid'), PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Add one user to n teams
     *
     * @param array<array-key, int> $teamIdArr this is the validated array of teams that exist
     */
    public function addUserToTeams(int $userid, array $teamIdArr): void
    {
        foreach ($teamIdArr as $teamId) {
            $params = new ContentParams('', '', array('userid' => $userid, 'teamid' => (int) $teamId));
            $this->create($params);
        }
    }

    /**
     * Remove one user from a team
     */
    public function destroy(ContentParamsInterface $params): bool
    {
        $userid = (int) $params->getExtra('userid');
        // make sure that the user is in more than one team before removing the team
        $UsersHelper = new UsersHelper($userid);
        if (count($UsersHelper->getTeamsFromUserid()) === 1) {
            return false;
        }
        $sql = 'DELETE FROM users2teams WHERE `users_id` = :userid AND `teams_id` = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', (int) $params->getExtra('teamid'), PDO::PARAM_INT);
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
            $params = new ContentParams('', '', array('userid' => $userid, 'teamid' => (int) $teamId));
            $this->destroy($params);
        }
    }
}
