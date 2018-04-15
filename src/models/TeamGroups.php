<?php
/**
 * \Elabftw\Elabftw\TeamGroups
 *
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Exception;
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
     * @return bool true if sql is successful
     */
    public function create(string $name): bool
    {
        $sql = "INSERT INTO team_groups(name, team) VALUES(:name, :team)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':team', $this->Users->userData['team']);

        return $req->execute();
    }

    /**
     * Read team groups
     *
     * @return array all team groups with users in group as array
     */
    public function readAll(): array
    {
        $fullGroups = array();

        $sql = "SELECT id, name FROM team_groups WHERE team = :team";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team']);
        $req->execute();

        $groups = $req->fetchAll();

        $sql = "SELECT DISTINCT CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM users CROSS JOIN users2team_groups
            ON (users2team_groups.userid = users.userid AND users2team_groups.groupid = :groupid)";
        $req = $this->Db->prepare($sql);

        foreach ($groups as $group) {
            $req->bindParam(':groupid', $group['id']);
            $req->execute();
            $usersInGroup = $req->fetchAll();
            $fullGroups[] = array(
                'id' => $group['id'],
                'name' => $group['name'],
                'users' => $usersInGroup
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

        $groups = $this->readAll();

        foreach ($groups as $group) {
            // only add the teamGroup to the list if user is part of it
            foreach ($group['users'] as $userInGroup) {
                if (in_array($this->Users->userData['fullname'], $userInGroup)) {
                    $idArr[] = $group['id'];
                    $nameArr[] = $group['name'];
                }
            }
        }
        $tgArr = array_combine($idArr, $nameArr);

        $visibilityArr = array(
            'public' => 'Public',
            'organization' => 'Everyone with an account',
            'team' => 'Only the team',
            'user' => 'Only me'
        );

        /** @noinspection AdditionOperationOnArraysInspection */
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
        $sql = "SELECT name FROM team_groups WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->execute();
        return $req->fetchColumn();
    }

    /**
     * Update the name of the group
     * The request comes from jeditable
     *
     * @param string $name Name of the group
     * @param string $id teamgroup_1
     * @throws Exception
     * @return string|null $name Name of the group if success
     */
    public function update(string $name, string $id): ?string
    {
        $idArr = explode('_', $id);
        if ($idArr[0] === 'teamgroup' && Tools::checkId((int) $idArr[1]) !== false) {
            $sql = "UPDATE team_groups SET name = :name WHERE id = :id AND team = :team";
            $req = $this->Db->prepare($sql);
            $req->bindParam(':name', $name);
            $req->bindParam(':team', $this->Users->userData['team']);
            $req->bindParam(':id', $idArr[1]);

            if ($req->execute()) {
                // the group name is returned so it gets back into jeditable input field
                return $name;
            }
        }
        throw new Exception('Cannot update team group!');
    }

    /**
     * Add or remove a member from a team group
     *
     * @param int $userId Id of the user
     * @param int $groupId Id of the group
     * @param string $action Can be 'add' or 'rm'
     * @throws Exception if the action keyword is wrong
     * @return bool true if success
     */
    public function updateMember(int $userId, int $groupId, string $action): bool
    {
        if ($action === 'add') {
            $sql = "INSERT INTO users2team_groups(userid, groupid) VALUES(:userid, :groupid)";
        } elseif ($action === 'rm') {
            $sql = "DELETE FROM users2team_groups WHERE userid = :userid AND groupid = :groupid";
        } else {
            throw new Exception('Bad action keyword');
        }
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userId, PDO::PARAM_INT);
        $req->bindParam(':groupid', $groupId, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Delete a team group
     *
     * @param int $id Id of the group to destroy
     * @throws Exception if it fails to delete
     * @return bool true on success
     */
    public function destroy(int $id): bool
    {
        $success = array();

        $sql = "UPDATE experiments SET visibility = 'team' WHERE visibility = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id);
        $success[] = $req->execute();

        $sql = "DELETE FROM team_groups WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $success[] = $req->execute();

        $sql = "DELETE FROM users2team_groups WHERE groupid = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $success[] = $req->execute();

        if (in_array(false, $success)) {
            throw new Exception('Error removing group');
        }

        return true;
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
        $sql = "SELECT DISTINCT userid FROM users2team_groups WHERE groupid = :groupid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':groupid', $groupid);
        $req->execute();
        $authUsersArr = array();
        while ($authUsers = $req->fetch()) {
            $authUsersArr[] = $authUsers['userid'];
        }

        return in_array($userid, $authUsersArr);
    }

    /**
     * Not implemented
     *
     */
    public function destroyAll(): bool
    {
        return false;
    }
}
