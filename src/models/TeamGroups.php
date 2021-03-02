<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\ParamsProcessor;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CrudInterface;
use function in_array;
use function is_bool;
use function mb_strlen;
use PDO;

/**
 * Everything related to the team groups
 */
class TeamGroups implements CrudInterface
{
    private Db $Db;

    private Users $Users;

    public function __construct(Users $users)
    {
        $this->Users = $users;
        $this->Db = Db::getConnection();
    }

    /**
     * Create a team group
     */
    public function create(ParamsProcessor $params): int
    {
        if (mb_strlen($params->name) < 2) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 2));
        }
        $sql = 'INSERT INTO team_groups(name, team) VALUES(:name, :team)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $params->name);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Read team groups
     *
     * @return array all team groups with users in group as array
     */
    public function read(): array
    {
        $fullGroups = array();

        $sql = 'SELECT DISTINCT id, name FROM team_groups CROSS JOIN users2teams ON (users2teams.teams_id = team_groups.team AND users2teams.teams_id = :team) ORDER BY name';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $groups = $req->fetchAll();
        if ($groups === false) {
            return $fullGroups;
        }

        $sql = "SELECT DISTINCT users.userid, CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM users
            CROSS JOIN users2team_groups ON (users2team_groups.userid = users.userid AND users2team_groups.groupid = :groupid)";
        $req = $this->Db->prepare($sql);

        foreach ($groups as $group) {
            $req->bindParam(':groupid', $group['id'], PDO::PARAM_INT);
            $this->Db->execute($req);
            $usersInGroup = $req->fetchAll();
            $fullGroups[] = array(
                'id' => $group['id'],
                'name' => $group['name'],
                'users' => $usersInGroup,
            );
        }

        return $fullGroups;
    }

    /**
     * When we need to build a select menu with visibility + team groups
     */
    public function getVisibilityList(): array
    {
        $idArr = array();
        $nameArr = array();

        $visibilityArr = array(
            'public' => _('Public'),
            'organization' => _('Everyone with an account'),
            'team' => _('Only the team'),
            'user' => _('Only me'),
        );
        $groups = $this->readGroupsFromUser();

        foreach ($groups as $group) {
            $idArr[] = $group['id'];
            $nameArr[] = $group['name'];
        }

        $tgArr = array_combine($idArr, $nameArr);
        if (is_bool($tgArr)) {
            return $visibilityArr;
        }

        return $visibilityArr + $tgArr;
    }

    /**
     * Get the name of a group
     */
    public function readName(int $id): string
    {
        $sql = 'SELECT name FROM team_groups WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetchColumn();
        if ($res === false || $res === null) {
            return '';
        }
        return (string) $res;
    }

    /**
     * Update the name of the group
     * The request comes from jeditable
     */
    public function update(ParamsProcessor $params): string
    {
        $sql = 'UPDATE team_groups SET name = :name WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $params->name, PDO::PARAM_STR);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $params->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        // the group name is returned so it gets back into jeditable input field
        return $params->name;
    }

    /**
     * Add or remove a member from a team group
     *
     * @param int $userid Id of the user
     * @param int $groupid Id of the group
     * @param string $action Can be 'add' or 'rm'
     * @throws IllegalActionException if the action keyword is wrong
     * @return void
     */
    public function updateMember(int $userid, int $groupid, string $action): void
    {
        if ($action === 'add') {
            $sql = 'INSERT INTO users2team_groups(userid, groupid) VALUES(:userid, :groupid)';
        } elseif ($action === 'rm') {
            $sql = 'DELETE FROM users2team_groups WHERE userid = :userid AND groupid = :groupid';
        } else {
            throw new IllegalActionException('Bad action keyword');
        }
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindParam(':groupid', $groupid, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Delete a team group
     */
    public function destroy(int $id): bool
    {
        // TODO add fk to do that
        $sql = "UPDATE experiments SET canread = 'team', canwrite = 'user' WHERE canread = :id OR canwrite = :id";
        $req = $this->Db->prepare($sql);
        // note: setting PDO::PARAM_INT here will throw error because the column type is varchar
        $req->bindParam(':id', $id, PDO::PARAM_STR);
        $res1 = $this->Db->execute($req);

        // same for items but canwrite is team
        $sql = "UPDATE items SET canread = 'team', canwrite = 'team' WHERE canread = :id OR canwrite = :id";
        $req = $this->Db->prepare($sql);
        // note: setting PDO::PARAM_INT here will throw error because the column type is varchar
        $req->bindParam(':id', $id, PDO::PARAM_STR);
        $res2 = $this->Db->execute($req);

        $sql = 'DELETE FROM team_groups WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $res3 = $this->Db->execute($req);

        $sql = 'DELETE FROM users2team_groups WHERE groupid = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $res4 = $this->Db->execute($req);

        return $res1 && $res2 && $res3 && $res4;
    }

    /**
     * Check if user is in a team group
     */
    public function isInTeamGroup(int $userid, int $groupid): bool
    {
        $sql = 'SELECT DISTINCT userid FROM users2team_groups WHERE groupid = :groupid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':groupid', $groupid, PDO::PARAM_INT);
        $this->Db->execute($req);
        $authUsersArr = array();
        while ($authUsers = $req->fetch()) {
            $authUsersArr[] = (int) $authUsers['userid'];
        }

        return in_array($userid, $authUsersArr, true);
    }

    /**
     * Check if both users are in the same group
     *
     * @param int $userid the other user
     */
    public function isUserInSameGroup(int $userid): bool
    {
        $sql = 'SELECT t1.groupid FROM users2team_groups AS t1
            INNER JOIN users2team_groups AS t2
            WHERE t1.userid = :userid AND t2.userid = :userid2';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':userid2', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        $req->fetch();
        return $req->rowCount() > 0;
    }

    public function getGroupsFromUser(): array
    {
        $groups = array();

        $sql = 'SELECT DISTINCT groupid FROM users2team_groups WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetchAll();
        if ($res === false) {
            return $groups;
        }
        foreach ($res as $group) {
            $groups[] = $group['groupid'];
        }
        return $groups;
    }

    public function readGroupsFromUser(): array
    {
        $sql = 'SELECT DISTINCT team_groups.id, team_groups.name
            FROM team_groups
            CROSS JOIN users2team_groups ON (
                users2team_groups.userid = :userid AND team_groups.id = users2team_groups.groupid
            )';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $groups = $req->fetchAll();
        if ($groups === false) {
            return array();
        }
        return $groups;
    }
}
