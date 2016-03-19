<?php
/**
 * \Elabftw\Elabftw\TeamGroups
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \PDO;
use \Exception;

/**
 * Everything related to the team groups
 */
class TeamGroups extends Admin
{
    /** The PDO object */
    private $pdo;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
        if (!$this->checkPermission()) {
            throw new Exception('Only admin can access this!');
        }
    }

    /**
     * Create a team group
     *
     * @param string $name Name of the group
     * @param string $team Team ID
     * @return bool true if sql is successful
     */
    public function create($name, $team)
    {
        $sql = "INSERT INTO team_groups(name, team) VALUES(:name, :team)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':team', $team);
        return $req->execute();
    }

    /**
     * Read team groups
     *
     * @return array all team groups
     */
    public function read($teamId)
    {
        $sql = "SELECT * FROM team_groups WHERE team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $teamId);
        $req->execute();
        return $req->fetchAll();
    }

    /**
     * Update the name of the group
     * The request comes from jeditable
     *
     * @param string $groupName Name of the group
     * @param string $groupId Id of the group
     * @throws Exception if sql fail
     * @return string|null $groupName Name of the group if success
     */
    public function update($groupName, $groupId)
    {
        $idArr = explode('_', $groupId);
        if ($idArr[0] === 'teamgroup' && is_pos_int($idArr[1])) {
            $sql = "UPDATE team_groups SET name = :name WHERE id = :id AND team = :team";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':name', $groupName);
            $req->bindParam(':team', $_SESSION['team_id']);
            $req->bindParam(':id', $idArr[1], \PDO::PARAM_INT);
            if ($req->execute()) {
                // the group name is returned so it gets back into jeditable input field
                return $groupName;
            } else {
                throw new Exception('Cannot update team group!');
            }
        }
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
        } else {
            return true;
        }
    }

    /**
     * Output html for displaying a list of existing team groups
     *
     * @param array $teamGroupsArr The full array from read()
     * @return string $html The HTML listing groups and users
     */
    public function show($teamGroupsArr)
    {
        $sql = "SELECT DISTINCT users.firstname, users.lastname
            FROM users CROSS JOIN users2team_groups
            ON (users2team_groups.userid = users.userid AND users2team_groups.groupid = :groupid)";

        $html = '';

        foreach ($teamGroupsArr as $teamGroup) {
            $html .= "<div class='well'><img onclick=\"teamGroupDestroy(" . $teamGroup['id'] . ", '" . str_replace(array("\"", "'"), '', _('Delete this?')) . "')\" src='img/small-trash.png' style='float:right' alt='trash' title='Remove this group' /><h3 class='inline editable teamgroup_name' id='teamgroup_" . $teamGroup['id'] . "'>" . $teamGroup['name'] . "</h3><ul>";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':groupid', $teamGroup['id']);
            $req->execute();
            while ($user = $req->fetch()) {
                $html .= "<li>" . $user['firstname'] . " " . $user['lastname'] . "</li>";
            }
            $html .= "</ul></div>";
        }
        return $html;
    }
}
