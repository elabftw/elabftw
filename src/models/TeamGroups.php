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

use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Scope;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Params\TeamGroupParams;
use Elabftw\Services\Filter;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

use function array_map;
use function explode;
use function json_decode;

/**
 * Everything related to the team groups
 */
class TeamGroups extends AbstractRest
{
    use SetIdTrait;

    public function __construct(private Users $Users, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create($reqBody['name'] ?? _('Untitled'));
    }

    public function getApiPath(): string
    {
        return sprintf('api/v2/teams/%d/teamgroups/', $this->Users->userData['team']);
    }

    /**
     * Read team groups of the current team
     *
     * @return array all team groups with users in group as array
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = "SELECT team_groups.id,
                team_groups.name,
                GROUP_CONCAT(users.userid ORDER BY users.firstname, users.lastname) AS userids,
                GROUP_CONCAT(CONCAT(users.firstname, ' ', users.lastname) ORDER BY users.firstname, users.lastname SEPARATOR '|') AS fullnames
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
                        fn(string $userid, ?string $fullname): array => array(
                            'userid' => (int) $userid,
                            'fullname' => $fullname,
                        ),
                        explode(',', $group['userids']),
                        explode('|', $group['fullnames'])
                    )
                    : array(),
            );
        }

        return $fullGroups;
    }

    public function readAllUser(): array
    {
        $sql = 'SELECT tg.id, tg.name, teams.name AS team_name
            FROM team_groups AS tg
            LEFT JOIN users2team_groups AS utg ON tg.id = utg.groupid
            LEFT JOIN teams ON (tg.team = teams.id)
            WHERE utg.userid = :userid ORDER BY teams.name, tg.name ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function readAllTeam(): array
    {
        $sql = 'SELECT team_groups.id, team_groups.name, teams.name AS team_name
            FROM team_groups LEFT JOIN teams ON (team_groups.team = teams.id) WHERE team_groups.team = :team ORDER BY teams.name, team_groups.name ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function readAllEverything(): array
    {
        $sql = 'SELECT team_groups.id, team_groups.name, teams.name AS team_name
            FROM team_groups LEFT JOIN teams ON (team_groups.team = teams.id) ORDER BY teams.name, team_groups.name ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function readScopedTeamgroups(): array
    {
        $scope = Scope::from($this->Users->userData['scope_teamgroups']);
        return match ($scope) {
            Scope::User => $this->readAllUser(),
            Scope::Team => $this->readAllTeam(),
            Scope::Everything => $this->readAllEverything(),
        };
    }

    /**
     * Get info about a team group
     */
    #[Override]
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
        $onlyIds = array_map('intval', $idArr);
        $sql = 'SELECT team_groups.name FROM team_groups WHERE id IN (' . implode(',', $onlyIds) . ') ORDER BY name ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    #[Override]
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

    #[Override]
    public function destroy(): bool
    {
        $this->canWriteOrExplode();

        $res1 = $this->updateTeamgroupPermissionsOnDestroy(EntityType::Experiments);
        $res2 = $this->updateTeamgroupPermissionsOnDestroy(EntityType::Items);

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

    private function create(string $name): int
    {
        if (!$this->Users->isAdmin) {
            throw new IllegalActionException(Tools::error(true));
        }
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
        $req->bindValue(':name', $params->getContent());
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
        $teamgroup = $this->readOne();
        if (!($this->Users->isAdmin && $this->Users->userData['team'] === $teamgroup['team'])) {
            throw new IllegalActionException(Tools::error(true));
        }
    }

    private function updateTeamgroupPermissionsOnDestroy(EntityType $entityType): bool
    {
        // the complicated SQL could be avoided if we could use JSON_SEARCH with integers but it only works with strings :(
        // https://bugs.mysql.com/bug.php?id=90085
        // we need to do a detour and convert int to string to get the index of the element that we want to remove
        // and we need to do it for canread and canwrite 🤯

        $sql = 'UPDATE %1$s AS entity
            -- canwrite join
            INNER JOIN (
                SELECT id,
                    -- find position of value of interest and remove it
                    JSON_REMOVE(entity.canwrite,
                        JSON_UNQUOTE(JSON_SEARCH(JSON_OBJECT(
                        "teamgroups",
                            JSON_ARRAYAGG(t_write.canwrite_str)
                        ), "one", :id))
                    ) AS new
                FROM %1$s AS entity
                -- convert int to string
                JOIN JSON_TABLE(
                    entity.canwrite->"$.teamgroups",
                    -- VARCHAR(10) can hold max int value
                    "$[*]" COLUMNS (canwrite_str VARCHAR(10) PATH "$")
                ) AS t_write
                -- collapse json table
                GROUP BY id
            ) t_canwrite
                ON (entity.id = t_canwrite.id)
            -- canread join
            INNER JOIN (
                SELECT id,
                    -- find position of value of interest and remove it
                    JSON_REMOVE(entity.canread,
                        JSON_UNQUOTE(JSON_SEARCH(JSON_OBJECT(
                        "teamgroups",
                            JSON_ARRAYAGG(t_read.canread_str)
                        ), "one", :id))
                    ) AS new
                FROM %1$s as entity
                -- convert int to string
                JOIN JSON_TABLE(
                    entity.canread->"$.teamgroups",
                    -- VARCHAR(10) can hold max int value
                    "$[*]" COLUMNS (canread_str VARCHAR(10) PATH "$")
                ) AS t_read
                -- collapse json table
                GROUP BY id
            ) t_canread
                ON (entity.id = t_canread.id)
            SET entity.canwrite = COALESCE(t_canwrite.new, entity.canwrite),
                entity.canread = COALESCE(t_canread.new, entity.canread)';

        $req = $this->Db->prepare(sprintf($sql, $entityType->value));
        $req->bindValue(':id', $this->id);
        return $this->Db->execute($req);
    }
}
