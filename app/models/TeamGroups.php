<?php
/**
 * \Elabftw\Elabftw\TeamGroups
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;
use Exception;

/**
 * Everything related to the team groups
 */
class TeamGroups
{
    /** The PDO object */
    private $pdo;

    /** our team */
    private $team;

    /**
     * Constructor
     *
     * @param int $team
     */
    public function __construct($team)
    {
        $this->team = $team;
        $this->pdo = Db::getConnection();
    }

    /**
     * Create a team group
     *
     * @param string $name Name of the group
     * @return bool true if sql is successful
     */
    public function create($name)
    {
        $sql = "INSERT INTO team_groups(name, team) VALUES(:name, :team)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':team', $this->team);

        return $req->execute();
    }

    /**
     * Read team groups
     *
     * @return array all team groups
     */
    public function readAll()
    {
        $sql = "SELECT id, name FROM team_groups WHERE team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $this->team);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * When we need to build a select menu with visibility + team groups
     *
     * @return array
     */
    public function readFull()
    {
        $idArr = array();
        $nameArr = array();

        $groups = $this->readAll();

        foreach ($groups as $group) {
            $idArr[] = $group['id'];
            $nameArr[] = $group['name'];
        }
        $tgArr = array_combine($idArr, $nameArr);

        $visibilityArr = array(
            'organization' => 'Everyone with an account',
            'team' => 'Only the team',
            'user' => 'Only me'
        );

        return $visibilityArr + $tgArr;
    }


    /**
     * Get the name of a group
     *
     * @param int $id
     * @return string
     */
    public function readName($id)
    {
        $sql = "SELECT name FROM team_groups WHERE id = :id";
        $req = $this->pdo->prepare($sql);
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
    public function update($name, $id)
    {
        $idArr = explode('_', $id);
        if ($idArr[0] === 'teamgroup' && Tools::checkId($idArr[1])) {
            $sql = "UPDATE team_groups SET name = :name WHERE id = :id AND team = :team";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':name', $name);
            $req->bindParam(':team', $this->team, PDO::PARAM_INT);
            $req->bindParam(':id', $idArr[1], PDO::PARAM_INT);

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
     * @param string $userId Id of the user
     * @param string $groupId Id of the group
     * @param string $action Can be 'add' or 'rm'
     * @throws Exception if the action keyword is wrong
     * @return bool true if success
     */
    public function updateMember($userId, $groupId, $action)
    {
        if ($action === 'add') {
            $sql = "INSERT INTO users2team_groups(userid, groupid) VALUES(:userid, :groupid)";
        } elseif ($action === 'rm') {
            $sql = "DELETE FROM users2team_groups WHERE userid = :userid AND groupid = :groupid";
        } else {
            throw new Exception('Bad action keyword');
        }
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $userId, \PDO::PARAM_INT);
        $req->bindParam(':groupid', $groupId, \PDO::PARAM_INT);
        return $req->execute();
    }

    /**
     * Delete a team group
     *
     * @param string $groupId Id of the group to destroy
     * @throws Exception if it fails to delete
     * @return bool true on success
     */
    public function destroy($groupId)
    {
        $success = array();

        $sql = "UPDATE experiments SET visibility = 'team' WHERE visibility = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $groupId);
        $success[] = $req->execute();

        $sql = "DELETE FROM team_groups WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $groupId, \PDO::PARAM_INT);
        $success[] = $req->execute();

        $sql = "DELETE FROM users2team_groups WHERE groupid = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $groupId, \PDO::PARAM_INT);
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
    public function isInTeamGroup($userid, $groupid)
    {
        $sql = "SELECT DISTINCT userid FROM users2team_groups WHERE groupid = :groupid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':groupid', $groupid);
        $req->execute();
        $authUsersArr = array();
        while ($authUsers = $req->fetch()) {
            $authUsersArr[] = $authUsers['userid'];
        }

        return in_array($userid, $authUsersArr);
    }
}
