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

use function array_combine;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Interfaces\TeamGroupParamsInterface;
use Elabftw\Traits\SetIdTrait;
use function in_array;
use PDO;

/**
 * Everything related to the team groups
 */
class TeamGroups implements CrudInterface
{
    use SetIdTrait;

    private Db $Db;

    public function __construct(private Users $Users, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->id = $id;
    }

    /**
     * Create a team group
     */
    public function create(ContentParamsInterface $params): int
    {
        $this->canWriteOrExplode();
        $sql = 'INSERT INTO team_groups(name, team) VALUES(:content, :team)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Read team groups
     *
     * @return array all team groups with users in group as array
     */
    public function read(ContentParamsInterface $params): array
    {
        $fullGroups = array();

        $sql = 'SELECT DISTINCT team_groups.id, team_groups.name FROM team_groups CROSS JOIN users2teams ON (users2teams.teams_id = team_groups.team AND users2teams.teams_id = :team) ORDER BY name';
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
            'user' => _('Only me and admins'),
            'useronly' => _('Only me'),
        );
        $groups = $this->readGroupsFromUser();

        foreach ($groups as $group) {
            $idArr[] = $group['id'];
            $nameArr[] = $group['name'];
        }

        $tgArr = array_combine($idArr, $nameArr);

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

    public function update(TeamGroupParamsInterface $params): bool
    {
        $this->canWriteOrExplode();
        if ($params->getTarget() === 'member') {
            return $this->updateMember($params);
        }
        $sql = 'UPDATE team_groups SET name = :name WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':name', $params->getContent(), PDO::PARAM_STR);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    public function destroy(): bool
    {
        $this->canWriteOrExplode();
        // TODO add fk to do that
        $sql = "UPDATE experiments SET canread = 'team', canwrite = 'user' WHERE canread = :id OR canwrite = :id";
        $req = $this->Db->prepare($sql);
        // note: setting PDO::PARAM_INT here will throw error because the column type is varchar
        $req->bindParam(':id', $this->id, PDO::PARAM_STR);
        $res1 = $this->Db->execute($req);

        // same for items but canwrite is team
        $sql = "UPDATE items SET canread = 'team', canwrite = 'team' WHERE canread = :id OR canwrite = :id";
        $req = $this->Db->prepare($sql);
        // note: setting PDO::PARAM_INT here will throw error because the column type is varchar
        $req->bindParam(':id', $this->id, PDO::PARAM_STR);
        $res2 = $this->Db->execute($req);

        $sql = 'DELETE FROM team_groups WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $res3 = $this->Db->execute($req);

        $sql = 'DELETE FROM users2team_groups WHERE groupid = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
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

        return $this->Db->fetchAll($req);
    }

    /**
     * Add or remove a member from a team group
     * How is add or rm
     */
    private function updateMember(TeamGroupParamsInterface $params): bool
    {
        if ($params->getHow() === 'add') {
            $sql = 'INSERT INTO users2team_groups(userid, groupid) VALUES(:userid, :groupid)';
        } elseif ($params->getHow() === 'rm') {
            $sql = 'DELETE FROM users2team_groups WHERE userid = :userid AND groupid = :groupid';
        } else {
            throw new IllegalActionException('Bad action keyword');
        }
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $params->getUserid(), PDO::PARAM_INT);
        $req->bindValue(':groupid', $params->getGroup(), PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Check if we can write to this teamgroup
     * We only need to check if we are admin
     */
    private function canWriteOrExplode(): void
    {
        if (!$this->Users->userData['is_admin']) {
            throw new IllegalActionException(Tools::error(true));
        }
    }
}
