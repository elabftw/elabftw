<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use function array_diff;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\ParamsProcessor;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\DestroyableInterface;
use Elabftw\Interfaces\ReadableInterface;
use Elabftw\Services\Filter;
use Elabftw\Services\TeamsHelper;
use Elabftw\Services\UsersHelper;
use PDO;

/**
 * All about the teams
 */
class Teams implements ReadableInterface, DestroyableInterface
{
    /** @var Users $Users instance of Users */
    public $Users;

    /** @var Db $Db SQL Database */
    protected $Db;

    /**
     * Constructor
     *
     * @param Users $users
     */
    public function __construct(Users $users)
    {
        $this->Db = Db::getConnection();
        $this->Users = $users;
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
                $team = $this->getTeamsFromIdOrNameOrOrgidArray(array($id));
            }
            $res[] = $team;
        }
        return $res;
    }

    /**
     * Add one user to n teams
     *
     * @param int $userid
     * @param array<array-key, int> $teamIdArr this is the validated array of teams that exist
     *
     * @return void
     */
    public function addUserToTeams(int $userid, array $teamIdArr): void
    {
        foreach ($teamIdArr as $teamId) {
            $TeamsHelper = new TeamsHelper((int) $teamId);
            // don't add a second time
            if ($TeamsHelper->isUserInTeam($userid)) {
                break;
            }
            $sql = 'INSERT INTO users2teams (`users_id`, `teams_id`) VALUES (:userid, :team);';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':userid', $userid, PDO::PARAM_INT);
            $req->bindParam(':team', $teamId, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    /**
     * Remove a user from teams
     *
     * @param int $userid
     * @param array<array-key, int> $teamIdArr this is the validated array of teams that exist
     *
     * @return void
     */
    public function rmUserFromTeams(int $userid, array $teamIdArr): void
    {
        // make sure that the user is in more than one team before removing the team
        $UsersHelper = new UsersHelper($userid);
        if (count($UsersHelper->getTeamsFromUserid()) === 1) {
            return;
        }
        foreach ($teamIdArr as $teamId) {
            $sql = 'DELETE FROM users2teams WHERE `users_id` = :userid AND `teams_id` = :team';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':userid', $userid, PDO::PARAM_INT);
            $req->bindParam(':team', $teamId, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    /**
     * When the user logs in, make sure that the teams they are part of
     * are the same teams than the one sent by an external auth
     *
     * @param int $userid
     * @param array<array-key, mixed> $teams
     *
     * @return void
     */
    public function synchronize(int $userid, array $teams): void
    {
        $teamIdArr = array_column($teams, 'id');
        // get the difference between the teams sent by idp
        // and the teams that the user is in
        $UsersHelper = new UsersHelper($userid);
        $currentTeams = $UsersHelper->getTeamsIdFromUserid();

        $addToTeams = array_diff($teamIdArr, $currentTeams);
        $this->addUserToTeams($userid, $addToTeams);
        $currentTeams = $UsersHelper->getTeamsIdFromUserid();

        $rmFromTeams = array_diff($currentTeams, $teamIdArr);
        $this->rmUserFromTeams($userid, $rmFromTeams);
    }

    /**
     * Add a new team
     *
     * @param string $name The new name of the team
     * @return int the new team id
     */
    public function create(string $name): int
    {
        $name = Filter::sanitize($name);

        // add to the teams table
        $sql = 'INSERT INTO teams (name, link_name, link_href) VALUES (:name, :link_name, :link_href)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindValue(':link_name', 'Documentation');
        $req->bindValue(':link_href', 'https://doc.elabftw.net');
        $this->Db->execute($req);
        // grab the team ID
        $newId = $this->Db->lastInsertId();

        // create default status
        $Status = new Status($this->Users);
        $Status->createDefault($newId);

        // create default item type
        $ItemsTypes = new ItemsTypes($this->Users);
        $ItemsTypes->create(
            new ParamsProcessor(
                array(
                    'name' => 'Edit me',
                    'color' => '#32a100',
                    'bookable' => 0,
                    'template' => '<p>Go to the admin panel to edit/add more items types!</p>',
                )
            ),
            $newId
        );

        // create default experiment template
        $Templates = new Templates($this->Users);
        $Templates->createDefault($newId);

        return $newId;
    }

    /**
     * Read from the current team
     *
     * @return array
     */
    public function read(): array
    {
        $sql = 'SELECT * FROM `teams` WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetch();
        if ($res === false) {
            return array();
        }

        return $res;
    }

    /**
     * Get all the teams
     *
     * @return array
     */
    public function readAll(): array
    {
        $sql = 'SELECT * FROM teams ORDER BY name ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Delete a team only if all the stats are at zero
     */
    public function destroy(int $id): bool
    {
        // check for stats, should be 0
        $count = $this->getStats($id);

        if ($count['totxp'] !== '0' || $count['totdb'] !== '0' || $count['totusers'] !== '0') {
            throw new ImproperActionException('The team is not empty! Aborting deletion!');
        }

        // foreign keys will take care of deleting associated data (like status or experiments_templates)
        $sql = 'DELETE FROM teams WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Clear the timestamp password
     *
     * @return bool
     */
    public function destroyStamppass(): bool
    {
        $sql = 'UPDATE teams SET stamppass = NULL WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Users->userData['team'], PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Get statistics for the whole install
     *
     * @return array
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
     *
     * @param int $team
     * @return array
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

    public function hasCommonTeamWithCurrent(int $userid, int $team): bool
    {
        $UsersHelper = new UsersHelper($userid);
        $teams = $UsersHelper->getTeamsIdFromUserid();
        return in_array((string) $team, $teams, true);
    }

    private function createTeamIfAllowed(string $name): int
    {
        $Config = new Config();
        if ($Config->configArr['saml_team_create']) {
            return $this->create($name);
        }
        throw new ImproperActionException('The administrator disabled team creation on login. Contact your administrator for creating the team beforehand.');
    }
}
