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
use Elabftw\Enums\Usergroup;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
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
    public function create(int $userid, int $teamid, int $group = 4): bool
    {
        // primary key will take care of ensuring there are no duplicate tuples
        $sql = 'INSERT IGNORE INTO users2teams (`users_id`, `teams_id`, `groups_id`) VALUES (:userid, :team, :group);';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $teamid, PDO::PARAM_INT);
        $req->bindValue(':group', $group, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function PatchUser2Team(Users $requester, array $params): int
    {
        $userid = (int) $params['userid'];
        $teamid = (int) $params['team'];
        if ($params['target'] === 'group') {
            $group = Usergroup::from((int) $params['content']);
            return $this->patchTeamGroup($requester, $userid, $teamid, $group);
        }

        // currently only other value for target is: is_owner
        return $this->patchIsOwner($requester, $userid, $teamid, $params['content']);
    }

    /**
     * Add one user to n teams
     *
     * @param array<array-key, int> $teamIdArr this is the validated array of teams that exist
     */
    public function addUserToTeams(int $userid, array $teamIdArr, int $group = 4): void
    {
        foreach ($teamIdArr as $teamId) {
            $this->create($userid, (int) $teamId, $group);
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

    private function patchTeamGroup(Users $requester, int $userid, int $teamid, Usergroup $group): int
    {
        $group = Check::usergroup($requester, $group)->value;
        $sql = 'UPDATE users2teams SET groups_id = :group WHERE `users_id` = :userid AND `teams_id` = :team';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':group', $group, PDO::PARAM_INT);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $teamid, PDO::PARAM_INT);

        $this->Db->execute($req);
        return $group;
    }

    private function patchIsOwner(Users $requester, int $userid, int $teamid, string $content): int
    {
        // only sysdamin can do that
        if ($requester->userData['is_sysadmin'] === 0) {
            throw new IllegalActionException('Only a sysadmin can modify is_owner value.');
        }
        $content = Filter::OnToBinary($content);
        $sql = 'UPDATE users2teams SET is_owner = :content WHERE `users_id` = :userid AND `teams_id` = :team';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $content, PDO::PARAM_INT);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $teamid, PDO::PARAM_INT);

        $this->Db->execute($req);
        return $content;
    }
}
