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
use Elabftw\Elabftw\TeamGroupParams;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\Filter;
use Elabftw\Traits\SetIdTrait;
use PDO;

use function array_map;
use function explode;
use function json_decode;

/**
 * Everything related to the team groups
 */
class TeamGroups implements RestInterface
{
    use SetIdTrait;

    private Db $Db;

    public function __construct(private Users $Users, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->setId($id);
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create($reqBody['name'] ?? _('Untitled'));
    }

    public function getPage(): string
    {
        return sprintf('api/v2/teams/%d/teamgroups/', $this->Users->userData['team']);
    }

    /**
     * Read team groups
     *
     * @return array all team groups with users in group as array
     */
    public function readAll(): array
    {
        $sql = "SELECT team_groups.id,
                team_groups.name,
                GROUP_CONCAT(users.userid ORDER BY users.firstname, users.lastname) AS userids,
                GROUP_CONCAT(CONCAT(users.firstname, ' ', users.lastname) ORDER BY users.firstname, users.lastname) AS fullnames
            FROM team_groups
            LEFT JOIN users2team_groups ON (
                users2team_groups.groupid = team_groups.id
            )
            LEFT JOIN users USING (userid)
            WHERE team_groups.team = :team
            GROUP BY team_groups.id
            ORDER BY team_groups.name ASC";

        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $fullGroups = array();
        while ($group = $req->fetch()) {
            $fullGroups[] = array(
                'id' => $group['id'],
                'name' => $group['name'],
                'users' => isset($group['userids'])
                    ? array_map(
                        function (string $userid, ?string $fullname): array {
                            return array(
                                'userid' => (int) $userid,
                                'fullname' => $fullname,
                            );
                        },
                        explode(',', $group['userids']),
                        explode(',', $group['fullnames'])
                    )
                    : array(),
            );
        }

        return $fullGroups;
    }

    public function readAllSimple(): array
    {
        $sql = 'SELECT team_groups.id, team_groups.name
            FROM team_groups WHERE team_groups.team = :team ORDER BY team_groups.name ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function readAllGlobal(): array
    {
        $sql = 'SELECT team_groups.id, team_groups.name
            FROM team_groups ORDER BY team_groups.name ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    /**
     * Get info about a team group
     */
    public function readOne(): array
    {
        $sql = 'SELECT * FROM team_groups WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    public function readNamesFromIds(array $idArr): array
    {
        if (empty($idArr)) {
            return array();
        }
        $sql = 'SELECT team_groups.name FROM team_groups WHERE id IN (' . implode(',', $idArr) . ') ORDER BY name ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function patch(Action $action, array $params): array
    {
        $this->canWriteOrExplode();
        match ($action) {
            Action::Update => (
                function () use ($params) {
                    if (!empty($params['how'])) {
                        return $this->updateMember($params);
                    }
                    foreach ($params as $key => $value) {
                        $this->update(new TeamGroupParams($key, (string) $value));
                    }
                }
            )(),
            default => throw new ImproperActionException('Invalid action for teamgroup'),
        };
        return $this->readOne();
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

        return $res1 && $res2 && $res3;
    }

    /**
     * Check if user is in a team group
     */
    public function isInTeamGroup(int $userid, int $groupid): bool
    {
        $sql = 'SELECT count(userid) FROM users2team_groups WHERE groupid = :groupid AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':groupid', $groupid, PDO::PARAM_INT);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchColumn() > '0';
    }

    public function readGroupsFromUser(): array
    {
        $sql = 'SELECT DISTINCT team_groups.id, team_groups.name
            FROM team_groups
            CROSS JOIN users2team_groups ON (
                users2team_groups.userid = :userid
                AND users2team_groups.groupid = team_groups.id
            )';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readGroupsWithUsersFromUser(): array
    {
        $sql = "SELECT team_groups_of_user.name,
                teams.name AS team_name,
                JSON_ARRAYAGG(JSON_OBJECT(
                    'userid', users.userid,
                    'fullname', CONCAT(users.firstname, ' ', users.lastname))) AS users
            FROM (
              -- get groups of a certain user
                SELECT team_groups.id,
                    team_groups.name,
                    team_groups.team
                FROM users2team_groups
                LEFT JOIN team_groups ON (
                  team_groups.id = users2team_groups.groupid
                )
                WHERE users2team_groups.userid = :userid
            ) AS team_groups_of_user
            -- now get all users of the groups
            LEFT JOIN users2team_groups ON (
                users2team_groups.groupid = team_groups_of_user.id
            )
            LEFT JOIN users USING (userid)
            LEFT JOIN teams ON (teams.id = team_groups_of_user.team)
            GROUP BY team_groups_of_user.id
            ORDER BY team_groups_of_user.name ASC";

        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $fullGroups = array();
        while ($group = $req->fetch()) {
            $fullGroups[] = array(
                'name' => $group['name'],
                'team' => $group['team_name'],
                'users' => empty($group['users'])
                    ? array()
                    : json_decode($group['users'], true),
            );
        }

        return $fullGroups;
    }

    /**
     * Create a team group
     */
    private function create(string $name): int
    {
        $this->canWriteOrExplode();
        $name = Filter::title($name);
        $sql = 'INSERT INTO team_groups(name, team) VALUES(:content, :team)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $name);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    private function update(TeamGroupParams $params): bool
    {
        $sql = 'UPDATE team_groups SET name = :name WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':name', $params->getContent(), PDO::PARAM_STR);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Add or remove a member from a team group
     * How is add or rm
     */
    private function updateMember(array $params): array
    {
        if ($params['how'] === Action::Add->value) {
            $sql = 'INSERT IGNORE INTO users2team_groups(userid, groupid) VALUES(:userid, :groupid)';
        } elseif ($params['how'] === Action::Unreference->value) {
            $sql = 'DELETE FROM users2team_groups WHERE userid = :userid AND groupid = :groupid';
        } else {
            throw new IllegalActionException('Bad action keyword');
        }
        $userid = (int) $params['userid'];
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':groupid', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->readOne();
    }

    /**
     * Check if we can write to this teamgroup
     * We only need to check if we are admin
     */
    private function canWriteOrExplode(): void
    {
        if (!$this->Users->isAdmin) {
            throw new IllegalActionException(Tools::error(true));
        }
    }
}
