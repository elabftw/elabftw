<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CrudInterface;
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
        if (\mb_strlen($name) < 2) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 2));
        }
        $sql = "INSERT INTO team_groups(name, team) VALUES(:name, :team)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
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
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $groups = $req->fetchAll();
        if ($groups === false) {
            return $fullGroups;
        }

        $sql = "SELECT DISTINCT CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM users CROSS JOIN users2team_groups
            ON (users2team_groups.userid = users.userid AND users2team_groups.groupid = :groupid)";
        $req = $this->Db->prepare($sql);

        foreach ($groups as $group) {
            $req->bindParam(':groupid', $group['id'], PDO::PARAM_INT);
            if ($req->execute() !== true) {
                throw new DatabaseErrorException('Error while executing SQL query.');
            }
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

        $visibilityArr = array(
            'public' => _('Public'),
            'organization' => _('Everyone with an account'),
            'team' => _('Only the team'),
            'user' => _('Only me')
        );
        $groups = $this->readAll();

        foreach ($groups as $group) {
            // only add the teamGroup to the list if user is part of it
            foreach ($group['users'] as $userInGroup) {
                if (\in_array($this->Users->userData['fullname'], $userInGroup, true)) {
                    $idArr[] = $group['id'];
                    $nameArr[] = $group['name'];
                }
            }
        }

        $tgArr = array_combine($idArr, $nameArr);
        if ($tgArr === false) {
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
        $sql = "SELECT name FROM team_groups WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        $res = $req->fetchColumn();
        if ($res === false || $res === null) {
            return '';
        }
        return $res;
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
        if (Tools::checkId((int) $idArr[1]) === false) {
            throw new IllegalActionException('Bad id');
        }
        $sql = "UPDATE team_groups SET name = :name WHERE id = :id AND team = :team";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $idArr[1], PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
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
            $sql = "INSERT INTO users2team_groups(userid, groupid) VALUES(:userid, :groupid)";
        } elseif ($action === 'rm') {
            $sql = "DELETE FROM users2team_groups WHERE userid = :userid AND groupid = :groupid";
        } else {
            throw new IllegalActionException('Bad action keyword');
        }
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindParam(':groupid', $groupid, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Delete a team group
     *
     * @param int $id Id of the group to destroy
     * @return void
     */
    public function destroy(int $id): void
    {
        $sql = "UPDATE experiments SET visibility = 'team' WHERE visibility = :id AND team = :team";
        $req = $this->Db->prepare($sql);
        // note: setting PDO::PARAM_INT here will throw error because it can also be string value!
        $req->bindParam(':id', $id);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $sql = "DELETE FROM team_groups WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $sql = "DELETE FROM users2team_groups WHERE groupid = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
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
        $req->bindParam(':groupid', $groupid, PDO::PARAM_INT);
        $req->execute();
        $authUsersArr = array();
        while ($authUsers = $req->fetch()) {
            $authUsersArr[] = (int) $authUsers['userid'];
        }

        return \in_array($userid, $authUsersArr, true);
    }

    /**
     * Not implemented
     *
     * @return void
     */
    public function destroyAll(): void
    {
        return;
    }
}
