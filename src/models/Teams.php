<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\TeamParam;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Models\Notifications\OnboardingEmail;
use Elabftw\Services\Filter;
use Elabftw\Services\TeamsHelper;
use Elabftw\Services\UsersHelper;
use Elabftw\Traits\SetIdTrait;
use PDO;
use RuntimeException;

use function array_diff;

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
        if ($id === null && ($Users->userData['team'] ?? 0) !== 0) {
            $id = (int) $Users->userData['team'];
        }
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
        $sql = 'SELECT id, name FROM teams WHERE id = :query OR name = :query OR orgid = :query';
        $req = $this->Db->prepare($sql);
        foreach ($input as $query) {
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
        $Users2Teams = new Users2Teams($this->Users);
        // send onboarding email of teams newly added to a user
        if ($this->Users->userData['validated']) {
            $Users2Teams->sendOnboardingEmailOfTeams = true;
        }
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
            Action::Create => $this->create($reqBody['name'] ?? 'New team name', $reqBody['default_category_name'] ?? _('Default')),
            default => throw new ImproperActionException('Incorrect action for teams.'),
        };
    }

    /**
     * Read one team
     */
    public function readOne(): array
    {
        $this->canReadOrExplode();
        $sql = 'SELECT * FROM `teams` WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    /**
     * Read all teams (only for sysadmin via api, otherwise set overrideReadPermissions to true)
     */
    public function readAll(): array
    {
        $this->canReadOrExplode();
        $sql = 'SELECT * FROM teams ORDER BY name ASC';
        $req = $this->Db->prepare($sql);
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
            Action::SendOnboardingEmails => $this->sendOnboardingEmails($params['userids']),
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

        /*
         * foreign keys will take care of deleting associated data (like status or experiments_templates)
         * IMPORTANT NOTE: the deletion of status will delete the experiments that have this status, too!
         * so even if the experiments have been moved around, if the status still belongs to the deleted team,
         * the experiment will get deleted.
         * so don't rely on fk to delete the status, but run it through the Status->delete first,
         * it will check if experiments have the status and show an error
         */
        $sql = 'SELECT id FROM experiments_status WHERE team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $statusArr = $req->fetchAll();
        $Status = new ExperimentsStatus($this);
        foreach ($statusArr as $status) {
            $Status->setId($status['id']);
            $Status->destroy();
        }

        $sql = 'DELETE FROM teams WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Get statistics for a team
     */
    public function getStats(int $team): array
    {
        $sql = 'SELECT
        (SELECT COUNT(users.userid) FROM users CROSS JOIN users2teams ON (users2teams.users_id = users.userid) WHERE users2teams.teams_id = :team) AS totusers,
        (SELECT COUNT(items.id) FROM items WHERE items.team = :team AND items.state = :state) AS totdb,
        (SELECT COUNT(experiments.id) FROM experiments LEFT JOIN users ON (experiments.userid = users.userid) CROSS JOIN users2teams ON (users2teams.users_id = users.userid) WHERE users2teams.teams_id = :team AND experiments.state = :state) AS totxp,
        (SELECT COUNT(experiments.id) FROM experiments LEFT JOIN users ON (experiments.userid = users.userid) CROSS JOIN users2teams ON (users2teams.users_id = users.userid) WHERE users2teams.teams_id = :team AND experiments.state = :state AND experiments.timestamped = 1) AS totxpts';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
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
        if ($this->bypassWritePermission || ($this->Users->userData['is_sysadmin'] ?? 0) === 1) {
            return;
        }
        if ($this->id === null) {
            throw new RuntimeException('Cannot check permissions in team because the team id is null.');
        }
        $TeamsHelper = new TeamsHelper($this->id);

        if ($TeamsHelper->isAdminInTeam((int) $this->Users->userData['userid'])) {
            return;
        }
        throw new IllegalActionException('User tried to update a team setting but they are not admin of that team.');
    }

    private function create(string $name, string $defaultCategoryName): int
    {
        $this->canWriteOrExplode();
        $name = Filter::title($name);

        $sql = 'INSERT INTO teams (name, common_template, common_template_md, link_name, link_href, force_canread, force_canwrite) VALUES (:name, :common_template, :common_template_md, :link_name, :link_href, :force_canread, :force_canwrite)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindValue(':common_template', Templates::defaultBody);
        $req->bindValue(':common_template_md', Templates::defaultBodyMd);
        $req->bindValue(':link_name', 'Documentation');
        $req->bindValue(':link_href', 'https://doc.elabftw.net');
        $req->bindValue(':force_canread', BasePermissions::Team->toJson());
        $req->bindValue(':force_canwrite', BasePermissions::Team->toJson());
        $this->Db->execute($req);
        // grab the team ID
        $newId = $this->Db->lastInsertId();
        $this->setId($newId);

        $user = new Users();
        // create default status
        $Status = new ExperimentsStatus($this);
        $Status->createDefault();

        // create default item type
        $user->team = $newId;
        $ItemsTypes = new ItemsTypes($user);
        // we can't patch something that is not in our team!
        $ItemsTypes->bypassWritePermission = true;
        $ItemsTypes->setId($ItemsTypes->create($defaultCategoryName));
        $defaultPermissions = BasePermissions::Team->toJson();
        $extra = array(
            'color' => '#32a100',
            'body' => '<p>This is the default text of the default category.</p><p>Head to the <a href="admin.php?tab=5">Admin Panel</a> to edit/add more categories for your database!</p>',
            'canread' => $defaultPermissions,
            'canwrite' => $defaultPermissions,
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
        if ($this->bypassReadPermission) {
            return;
        }
        if ($this->id === null) {
            throw new RuntimeException('Cannot check permissions in team because the team id is null.');
        }

        if ($this->Users->userData['is_sysadmin'] === 1) {
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
            $this->bypassWritePermission = true;
            return $this->postAction(Action::Create, array('name' => $name));
        }
        throw new ImproperActionException('The administrator disabled team creation on login. Contact your administrator for creating the team beforehand.');
    }

    private function sendOnboardingEmails(array $userids): void
    {
        // validate that userid is part of team and active
        foreach(array_intersect(array_column($this->Users->readAllActiveFromTeam(), 'userid'), $userids) as $userid) {
            /** @psalm-suppress PossiblyNullArgument */
            (new OnboardingEmail($this->id))->create($userid);
        }
    }
}
