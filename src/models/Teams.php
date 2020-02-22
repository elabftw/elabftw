<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Services\Filter;
use Elabftw\Services\UsersHelper;
use PDO;

/**
 * All about the teams
 */
class Teams implements CrudInterface
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
     * Check if the team exists from the id
     *
     * @param int $id team id
     * @return bool
     */
    public function isExisting(int $id): bool
    {
        $sql = 'SELECT id FROM teams WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (bool) $req->fetch();
    }

    /**
     * Transform a team name/orgid in the team id
     *
     * @param string $query name or orgid of the team
     * @return int
     */
    public function getTeamIdFromNameOrOrgid(string $query): int
    {
        $sql = 'SELECT id FROM teams WHERE id = :query OR name = :query OR orgid = :query';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':query', $query);
        $this->Db->execute($req);
        $res = $req->fetchColumn();
        if ($res === false) {
            throw new ImproperActionException('Could not find team!');
        }
        return (int) $res;
    }

    /**
     * Make sure that all the teams are existing
     * If they do not exist, create them if it's allowed by sysadmin
     *
     * @param array $teams
     * @return array an array of teams id
     */
    public function validateTeams(array $teams): array
    {
        $Config = new Config();
        $teamIdArr = array();
        foreach ($teams as $team) {
            try {
                $teamIdArr[] = $this->getTeamIdFromNameOrOrgid($team);
            } catch (ImproperActionException $e) {
                if ($Config->configArr['saml_team_create']) {
                    $teamIdArr[] = $this->create($team);
                } else {
                    throw new ImproperActionException('The administrator disabled team creation on SAML login. Contact your administrator for creating the team.');
                }
            }
        }
        return $teamIdArr;
    }

    /**
     * Add one user to n teams
     *
     * @param int $userid
     * @param array $teamIdArr this is the validated array of teams that exist coming from validateTeams
     *
     * @return void
     */
    public function addUserToTeams(int $userid, array $teamIdArr): void
    {
        foreach ($teamIdArr as $teamId) {
            // don't add a second time
            if ($this->isUserInTeam($userid, (int) $teamId)) {
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
     * @param array $teamIdArr this is the validated array of teams that exist coming from validateTeams
     *
     * @return void
     */
    public function rmUserFromTeams(int $userid, array $teamIdArr): void
    {
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
     * are the same teams than the one sent by the IDP
     *
     * @param int $userid
     * @param array $teams
     *
     * @return void
     */
    public function syncFromIdp(int $userid, array $teams): void
    {
        $teamIdArr = $this->validateTeams($teams);
        // get the difference between the teams sent by idp
        // and the teams that the user is in
        $UsersHelper = new UsersHelper();
        $currentTeams = $UsersHelper->getTeamsIdFromUserid($userid);

        $addToTeams = \array_diff($teamIdArr, $currentTeams);
        $rmFromTeams =\array_diff($currentTeams, $teamIdArr);

        $this->rmUserFromTeams($userid, $rmFromTeams);
        $this->addUserToTeams($userid, $addToTeams);
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
            'Edit me',
            '#32a100',
            0,
            '<p>Go to the admin panel to edit/add more items types!</p>',
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
     * Update team
     *
     * @param array $post POST
     * @return void
     */
    public function update(array $post): void
    {
        // CHECKS
        /* TODO provide an upload button
        if (isset($post['stampcert'])) {
            $cert_chain = filter_var($post['stampcert'], FILTER_SANITIZE_STRING);
            $elabRoot = \dirname(__DIR__, 2);
            if (!is_readable(realpath($elabRoot . '/web/' . $cert_chain))) {
                throw new Exception('Cannot read provided certificate file.');
            }
        }
         */

        if (isset($post['stamppass']) && !empty($post['stamppass'])) {
            $stamppass = Crypto::encrypt($post['stamppass'], Key::loadFromAsciiSafeString(\SECRET_KEY));
        } else {
            $teamConfigArr = $this->read();
            $stamppass = $teamConfigArr['stamppass'];
        }

        $deletableXp = 0;
        if ($post['deletable_xp'] == 1) {
            $deletableXp = 1;
        }

        $publicDb = 0;
        if ($post['public_db'] == 1) {
            $publicDb = 1;
        }

        $linkName = 'Documentation';
        if (isset($post['link_name'])) {
            $linkName = Filter::sanitize($post['link_name']);
        }

        $linkHref = 'https://doc.elabftw.net';
        if (isset($post['link_href'])) {
            $linkHref = Filter::sanitize($post['link_href']);
        }

        $sql = 'UPDATE teams SET
            deletable_xp = :deletable_xp,
            public_db = :public_db,
            link_name = :link_name,
            link_href = :link_href,
            stamplogin = :stamplogin,
            stamppass = :stamppass,
            stampprovider = :stampprovider,
            stampcert = :stampcert
            WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':deletable_xp', $deletableXp, PDO::PARAM_INT);
        $req->bindParam(':public_db', $publicDb, PDO::PARAM_INT);
        $req->bindParam(':link_name', $linkName);
        $req->bindParam(':link_href', $linkHref);
        $req->bindParam(':stamplogin', $post['stamplogin']);
        $req->bindParam(':stamppass', $stamppass);
        $req->bindParam(':stampprovider', $post['stampprovider']);
        $req->bindParam(':stampcert', $post['stampcert']);
        $req->bindParam(':id', $this->Users->userData['team'], PDO::PARAM_INT);

        $this->Db->execute($req);
    }

    /**
     * Edit the name of a team, called by ajax
     *
     * @param int $id The id of the team
     * @param string $name The new name we want
     * @param string $orgid The id of the team in the organisation (from IDP for instance)
     * @return void
     */
    public function updateName(int $id, string $name, string $orgid = ''): void
    {
        $name = Filter::sanitize($name);
        $orgid = Filter::sanitize($orgid);

        $sql = 'UPDATE teams
            SET name = :name,
                orgid = :orgid
            WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':orgid', $orgid);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Delete a team only if all the stats are at zero
     *
     * @param int $id ID of the team
     * @return void
     */
    public function destroy(int $id): void
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
        $this->Db->execute($req);
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

        return $req->fetch(PDO::FETCH_NAMED);
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

        return $req->fetch(PDO::FETCH_NAMED);
    }

    // Check if two users have at least one team in common
    public function hasCommonTeam(int $useridA, int $useridB): bool
    {
        $UsersHelper = new UsersHelper();
        $teamsA = $UsersHelper->getTeamsIdFromUserid($useridA);
        $teamsB = $UsersHelper->getTeamsIdFromUserid($useridB);
        if (\count(\array_intersect($teamsA, $teamsB)) > 0) {
            return true;
        }
        return false;
    }

    public function isUserInTeam(int $userid, int $team): bool
    {
        $sql = 'SELECT `users_id` FROM `users2teams` WHERE `teams_id` = :team AND `users_id` = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (bool) $req->fetchColumn();
    }
}
