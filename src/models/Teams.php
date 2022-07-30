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
use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\ItemTypeParams;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Services\UsersHelper;
use Elabftw\Traits\SetIdTrait;
use PDO;

/**
 * All about the teams
 */
class Teams implements CrudInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(public Users $Users, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->id = $id;
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

    /**
     * Add a new team
     */
    public function create(ContentParamsInterface $params): int
    {
        $name = $params->getContent();

        // add to the teams table
        $sql = 'INSERT INTO teams (name, common_template, link_name, link_href) VALUES (:name, :common_template, :link_name, :link_href)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindValue(':common_template', Templates::defaultBody);
        $req->bindValue(':link_name', 'Documentation');
        $req->bindValue(':link_href', 'https://doc.elabftw.net');
        $this->Db->execute($req);
        // grab the team ID
        $newId = $this->Db->lastInsertId();

        // create default status
        $Status = new Status($newId);
        $Status->createDefault();

        // create default item type
        $user = new Users();
        $user->team = $newId;
        $ItemsTypes = new ItemsTypes($user);
        $extra = array(
            'color' => '#32a100',
            'body' => '<p>Go to the admin panel to edit/add more items types!</p>',
            'canread' => 'team',
            'canwrite' => 'team',
            'bookable' => '0',
        );
        $ItemsTypes->create(new ItemTypeParams('Edit me', 'all', $extra));

        return $newId;
    }

    /**
     * Read from the current team
     */
    public function readOne(): array
    {
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
        $sql = 'SELECT * FROM teams ORDER BY name ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function update(ContentParamsInterface $params): bool
    {
        if (!$this->Users->userData['is_admin']) {
            throw new IllegalActionException('Non admin user tried to update a team setting.');
        }

        $column = $params->getTarget();
        try {
            $content = $params->getContent();
            // allow ts_login to be empty
        } catch (ImproperActionException $e) {
            if ($column === 'ts_login') {
                $content = null;
            } else {
                throw new ImproperActionException('Input is too short!', 400, $e);
            }
        }

        $sql = 'UPDATE teams SET ' . $column . ' = :content WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':content', $content);
        $req->bindParam(':id', $this->Users->userData['team'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Delete a team only if all the stats are at zero
     */
    public function destroy(): bool
    {
        // check for stats, should be 0
        $count = $this->getStats($this->id);

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
     * Clear the timestamp password
     */
    public function destroyStamppass(): bool
    {
        $sql = 'UPDATE teams SET ts_password = NULL WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->Users->userData['team'], PDO::PARAM_INT);

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

    public function hasCommonTeamWithCurrent(int $userid, int $team): bool
    {
        $UsersHelper = new UsersHelper($userid);
        $teams = $UsersHelper->getTeamsIdFromUserid();
        return in_array($team, $teams, true);
    }

    private function createTeamIfAllowed(string $name): int
    {
        $Config = Config::getConfig();
        if ($Config->configArr['saml_team_create']) {
            return $this->create(new ContentParams($name));
        }
        throw new ImproperActionException('The administrator disabled team creation on login. Contact your administrator for creating the team beforehand.');
    }
}
