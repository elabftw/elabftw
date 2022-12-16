<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use function array_diff;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\PermissionsDefaults;
use Elabftw\Elabftw\TeamParam;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\Filter;
use Elabftw\Services\UsersHelper;
use Elabftw\Traits\SetIdTrait;
use PDO;

/**
 * All about the teams
 */
class Teams implements RestInterface
{
    use SetIdTrait;

    public bool $bypassWritePermission = false;

    public bool $bypassReadPermission = false;

    protected Db $Db;

    public function __construct(public Users $Users, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->setId($id);
    }

    /**
     * Make sure that the teams exist. Input can be an array of team name, id or orgid
     * and the response is an array of teams, with id and name for each
     * Input can come from external auth and reference an uncreated team
     * so with this the team will be created on the fly (if it's allowed)
     */
    public function getTeamsFromIdOrNameOrOrgidArray(array $input): array
    {
        $res = array();
        foreach ($input as $query) {
            $sql = 'SELECT id, name FROM teams WHERE id = :query OR name = :query OR orgid = :query';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':query', $query);
            $this->Db->execute($req);
            $team = $req->fetch();
            if ($team === false) {
                $id = $this->createTeamIfAllowed($query);
                $team = $this->getTeamsFromIdOrNameOrOrgidArray(array($id))[0];
            }
            $res[] = $team;
        }
        return $res;
    }

    /**
     * When the user logs in, make sure that the teams they are part of
     * are the same teams than the one sent by an external auth
     *
     * @param array<array-key, mixed> $teams
     */
    public function synchronize(int $userid, array $teams): void
    {
        $Users2Teams = new Users2Teams();
        $teamIdArr = array_column($teams, 'id');
        // get the difference between the teams sent by idp
        // and the teams that the user is in
        $UsersHelper = new UsersHelper($userid);
        $currentTeams = $UsersHelper->getTeamsIdFromUserid();

        $addToTeams = array_diff($teamIdArr, $currentTeams);
        $Users2Teams->addUserToTeams($userid, $addToTeams);
        $currentTeams = $UsersHelper->getTeamsIdFromUserid();

        $rmFromTeams = array_diff($currentTeams, $teamIdArr);
        $Users2Teams->rmUserFromTeams($userid, $rmFromTeams);
    }

    public function getPage(): string
    {
        return 'api/v2/teams/';
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return match ($action) {
            Action::Create => $this->create($reqBody['name'] ?? 'New team name'),
            default => throw new ImproperActionException('Incorrect action for teams.'),
        };
    }

    /**
     * Read from the current team
     */
    public function readOne(): array
    {
        $this->canReadOrExplode();
        $sql = 'SELECT * FROM `teams` WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    /**
     * Get all the teams
     */
    public function readAll(): array
    {
        $this->canReadOrExplode();
        $sql = 'SELECT * FROM teams ORDER BY name ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readMyTeams(): array
    {
        $this->canReadOrExplode();
        $sql = 'SELECT teams.id, teams.name, teams.orgid FROM teams CROSS JOIN users2teams ON (users2teams.teams_id = teams.id AND users2teams.users_id = :userid) WHERE users2teams.users_id = :userid ORDER BY name ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readNamesFromIds(array $idArr): array
    {
        if (empty($idArr)) {
            return array();
        }
        $sql = 'SELECT teams.name FROM teams WHERE id IN (' . implode(',', $idArr) . ') ORDER BY name ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function patch(Action $action, array $params): array
    {
        $this->canWriteOrExplode();

        match ($action) {
            Action::Archive => throw new ImproperActionException('Feature not implemented.'),
            Action::Update => (
                function () use ($params) {
                    foreach ($params as $key => $value) {
                        $this->update(new TeamParam($key, (string) $value));
                    }
                }
            )(),
            default => throw new ImproperActionException('Incorrect action for teams.'),
        };
        return $this->readOne();
    }

    /**
     * Delete a team only if all the stats are at zero
     */
    public function destroy(): bool
    {
        // check for stats, should be 0
        $count = $this->getStats($this->id ?? 0);

        if ($count['totxp'] !== 0 || $count['totdb'] !== 0 || $count['totusers'] !== 0) {
            throw new ImproperActionException('The team is not empty! Aborting deletion!');
        }

        // foreign keys will take care of deleting associated data (like status or experiments_templates)
        $sql = 'DELETE FROM teams WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Get statistics for the whole install
     */
    public function getAllStats(): array
    {
        $sql = 'SELECT
        (SELECT COUNT(users.userid) FROM users) AS totusers,
        (SELECT COUNT(items.id) FROM items) AS totdb,
        (SELECT COUNT(teams.id) FROM teams) AS totteams,
        (SELECT COUNT(experiments.id) FROM experiments) AS totxp,
        (SELECT COUNT(experiments.id) FROM experiments WHERE experiments.timestamped = 1) AS totxpts';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        $res = $req->fetch(PDO::FETCH_NAMED);
        if ($res === false) {
            return array();
        }

        return $res;
    }

    /**
     * Get statistics for a team
     */
    public function getStats(int $team): array
    {
        $sql = 'SELECT
        (SELECT COUNT(users.userid) FROM users CROSS JOIN users2teams ON (users2teams.users_id = users.userid) WHERE users2teams.teams_id = :team) AS totusers,
        (SELECT COUNT(items.id) FROM items WHERE items.team = :team) AS totdb,
        (SELECT COUNT(experiments.id) FROM experiments LEFT JOIN users ON (experiments.userid = users.userid) CROSS JOIN users2teams ON (users2teams.users_id = users.userid) WHERE users2teams.teams_id = :team) AS totxp,
        (SELECT COUNT(experiments.id) FROM experiments LEFT JOIN users ON (experiments.userid = users.userid) CROSS JOIN users2teams ON (users2teams.users_id = users.userid) WHERE users2teams.teams_id = :team AND experiments.timestamped = 1) AS totxpts';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetch(PDO::FETCH_NAMED);
        if ($res === false) {
            return array();
        }

        return $res;
    }

    public function hasCommonTeamWithCurrent(int $userid, ?int $team = null): bool
    {
        if ($userid === 0) {
            return true;
        }
        if ($team === null) {
            $team = $this->Users->userData['team'];
        }
        $UsersHelper = new UsersHelper($userid);
        $teams = $UsersHelper->getTeamsIdFromUserid();
        return in_array($team, $teams, true);
    }

    public function canWriteOrExplode(): void
    {
        if ($this->bypassWritePermission) {
            return;
        }
        if ($this->Users->userData['is_sysadmin'] || ($this->Users->userData['is_admin'] && $this->hasCommonTeamWithCurrent($this->Users->userData['userid'], $this->id))) {
            return;
        }
        throw new IllegalActionException('User tried to update a team setting but they are not admin of that team.');
    }

    private function create(string $name): int
    {
        $name = Filter::title($name);

        $sql = 'INSERT INTO teams (name, common_template, common_template_md, link_name, link_href, force_canread, force_canwrite) VALUES (:name, :common_template, :common_template_md, :link_name, :link_href, :force_canread, :force_canwrite)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindValue(':common_template', Templates::defaultBody);
        $req->bindValue(':common_template_md', Templates::defaultBodyMd);
        $req->bindValue(':link_name', 'Documentation');
        $req->bindValue(':link_href', 'https://doc.elabftw.net');
        $req->bindValue(':force_canread', PermissionsDefaults::MY_TEAMS);
        $req->bindValue(':force_canwrite', PermissionsDefaults::USER);
        $this->Db->execute($req);
        // grab the team ID
        $newId = $this->Db->lastInsertId();

        $user = new Users();
        // create default status
        $Status = new Status(new self($user, $newId));
        $Status->createDefault();

        // create default item type
        $user->team = $newId;
        $ItemsTypes = new ItemsTypes($user);
        $ItemsTypes->setId($ItemsTypes->create('Edit me'));
        // we can't patch something that is not in our team!
        $ItemsTypes->bypassWritePermission = true;
        $defaultPermissions = PermissionsDefaults::MY_TEAMS;
        $extra = array(
            'color' => '#32a100',
            'body' => '<p>This is the default text of the default category.</p><p>Head to the <a href="admin.php?tab=5">Admin Panel</a> to edit/add more categories for your database!</p>',
            'canread' => $defaultPermissions,
            'canwrite' => $defaultPermissions,
            'bookable' => '0',
        );
        $ItemsTypes->patch(Action::Update, $extra);

        return $newId;
    }

    private function update(TeamParam $params): bool
    {
        $sql = 'UPDATE teams SET ' . $params->getColumn() . ' = :content WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function canReadOrExplode(): void
    {
        if ($this->bypassReadPermission || $this->Users->userData['is_sysadmin']) {
            return;
        }
        if ($this->hasCommonTeamWithCurrent((int) $this->Users->userData['userid'], $this->id)) {
            return;
        }
        throw new IllegalActionException('User tried to read a team setting but they are not part of that team.');
    }

    private function createTeamIfAllowed(string $name): int
    {
        $Config = Config::getConfig();
        if ($Config->configArr['saml_team_create']) {
            return $this->postAction(Action::Create, array('name' => $name));
        }
        throw new ImproperActionException('The administrator disabled team creation on login. Contact your administrator for creating the team beforehand.');
    }
}
