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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Services\Check;
use function is_bool;
use PDO;

/**
 * Everything related to the team groups
 */
class TeamGroups implements CrudInterface
{
    /** @var Db $Db SQL Database */
    private $Db;

    /** @var Users $Users instance of Users */
    private $Users;

    /**
     * Constructor
     *
     * @param Users $users
     */
    public function __construct(Users $users)
    {
        $this->Users = $users;
        $this->Db = Db::getConnection();
    }

    /**
     * Create a team group
     *
     * @param string $name Name of the group
     * @return void
     */
    public function create(string $name): void
    {
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        if ($name === false || \mb_strlen($name) < 2) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 2));
        }
        $sql = 'INSERT INTO team_groups(name, team) VALUES(:name, :team)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Read team groups
     *
     * @return array all team groups with users in group as array
     */
    public function readAll(): array
    {
        $fullGroups = array();

        $sql = 'SELECT DISTINCT id, name FROM team_groups CROSS JOIN users2teams ON (users2teams.teams_id = team_groups.team AND users2teams.teams_id = :team)';
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
     *
     * @return array
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
     *
     * @param int $id
     * @return string
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
     *
     * @param string $name Name of the group
     * @param string $id teamgroup_1
     * @return string $name Name of the group if success
     */
    public function update(string $name, string $id): string
    {
        $idArr = explode('_', $id);
        if (Check::id((int) $idArr[1]) === false) {
            throw new IllegalActionException('Bad id');
        }
        $sql = 'UPDATE team_groups SET name = :name WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $idArr[1], PDO::PARAM_INT);
        $this->Db->execute($req);
        // the group name is returned so it gets back into jeditable input field
        return $name;
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
     *
     * @param int $id Id of the group to destroy
     * @return void
     */
    public function destroy(int $id): void
    {
        // TODO add fk to do that
        $sql = "UPDATE experiments SET canread = 'team', canwrite = 'user' WHERE canread = :id OR canwrite = :id";
        $req = $this->Db->prepare($sql);
        // note: setting PDO::PARAM_INT here will throw error because the column type is varchar
        $req->bindParam(':id', $id, PDO::PARAM_STR);
        $this->Db->execute($req);

        // same for items but canwrite is team
        $sql = "UPDATE items SET canread = 'team', canwrite = 'team' WHERE canread = :id OR canwrite = :id";
        $req = $this->Db->prepare($sql);
        // note: setting PDO::PARAM_INT here will throw error because the column type is varchar
        $req->bindParam(':id', $id, PDO::PARAM_STR);
        $this->Db->execute($req);

        $sql = 'DELETE FROM team_groups WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $sql = 'DELETE FROM users2team_groups WHERE groupid = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Check if user is in a team group
     *
     * @param int $userid
     * @param int $groupid
     * @return bool
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

        return \in_array($userid, $authUsersArr, true);
    }

    /**
     * Check if both users are in the same group
     *
     * @param int $userid the other user
     * @return bool
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
