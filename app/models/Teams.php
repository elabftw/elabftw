<?php
/**
 * \Elabftw\Elabftw\Teams
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;
use Exception;
use Defuse\Crypto\Crypto as Crypto;
use Defuse\Crypto\Key as Key;

/**
 * All about the teams
 */
class Teams implements CrudInterface
{
    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var Users $Users instance of Users */
    public $Users;

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
     * Check if the team exists already and create one if not
     *
     * @param string $name Name of the team (case sensitive)
     * @return int The team ID
     */
    public function initializeIfNeeded($name)
    {
        $sql = 'SELECT team_id, team_name, team_orgid FROM teams';
        $req = $this->Db->prepare($sql);
        $req->execute();
        $teamsArr = $req->fetchAll();
        foreach ($teamsArr as $team) {
            if (($team['team_name'] === $name) || ($team['team_orgid'] === $name)) {
                return $team['team_id'];
            }
        }
        return $this->create($name);
    }

    /**
     * Add a new team
     *
     * @param string $name The new name of the team
     * @return string|false false on error, new team id otherwise
     */
    public function create($name)
    {
        $name = filter_var($name, FILTER_SANITIZE_STRING);

        // add to the teams table
        $sql = 'INSERT INTO teams (team_name, link_name, link_href) VALUES (:team_name, :link_name, :link_href)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team_name', $name);
        $req->bindValue(':link_name', 'Documentation');
        $req->bindValue(':link_href', 'https://doc.elabftw.net');
        $result1 = $req->execute();
        // grab the team ID
        $newId = $this->Db->lastInsertId();

        // create default status
        $Status = new Status($this->Users);
        $result2 = $Status->createDefault($newId);

        // create default item type
        $ItemsTypes = new ItemsTypes($this->Users);
        $result3 = $ItemsTypes->create(
            'Edit me',
            '32a100',
            0,
            '<p>Go to the admin panel to edit/add more items types!</p>',
            $newId
        );

        // create default experiment template
        $Templates = new Templates($this->Users);
        $result4 = $Templates->createDefault($newId);

        if ($result1 && $result2 && $result3 && $result4) {
            return $newId;
        }
        return false;

    }

    /**
     * Read from a team
     *
     * @param string|null $column
     * @return array|string
     */
    public function read($column = null)
    {
        $sql = "SELECT * FROM `teams` WHERE team_id = :team_id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team_id', $this->Users->userData['team']);
        $req->execute();
        $teamConfig = $req->fetch();
        if (is_null($column)) {
            return $teamConfig;
        }
        return $teamConfig[$column];
    }

    /**
     * Get all the teams
     *
     * @return array
     */
    public function readAll()
    {
        $sql = "SELECT * FROM teams ORDER BY team_name ASC";
        $req = $this->Db->prepare($sql);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Update team
     *
     * @param array $post POST
     * @return bool
     */
    public function update($post)
    {
        // CHECKS
        if (isset($post['stampcert'])) {
            $cert_chain = filter_var($post['stampcert'], FILTER_SANITIZE_STRING);
            if (!is_readable(realpath(ELAB_ROOT . $cert_chain))) {
                throw new Exception('Cannot read provided certificate file.');
            }
        }

        if (isset($post['stamppass']) && !empty($post['stamppass'])) {
            $stamppass = Crypto::encrypt($post['stamppass'], Key::loadFromAsciiSafeString(SECRET_KEY));
        } else {
            $stamppass = $this->read('stamppass');
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
            $linkName = filter_var($post['link_name'], FILTER_SANITIZE_STRING);
        }

        $linkHref = 'https://doc.elabftw.net';
        if (isset($post['link_href'])) {
            $linkHref = filter_var($post['link_href'], FILTER_SANITIZE_STRING);
        }

        $sql = "UPDATE teams SET
            deletable_xp = :deletable_xp,
            public_db = :public_db,
            link_name = :link_name,
            link_href = :link_href,
            stamplogin = :stamplogin,
            stamppass = :stamppass,
            stampprovider = :stampprovider,
            stampcert = :stampcert
            WHERE team_id = :team_id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':deletable_xp', $deletableXp);
        $req->bindParam(':public_db', $publicDb);
        $req->bindParam(':link_name', $linkName);
        $req->bindParam(':link_href', $linkHref);
        $req->bindParam(':stamplogin', $post['stamplogin']);
        $req->bindParam(':stamppass', $stamppass);
        $req->bindParam(':stampprovider', $post['stampprovider']);
        $req->bindParam(':stampcert', $post['stampcert']);
        $req->bindParam(':team_id', $this->Users->userData['team']);

        return $req->execute();
    }

    /**
     * Edit the name of a team, called by ajax
     *
     * @param int $id The id of the team
     * @param string $name The new name we want
     * @param string $orgid The id of the team in the organisation (from IDP for instance)
     * @return bool
     */
    public function updateName($id, $name, $orgid = "")
    {
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $sql = "UPDATE teams
            SET team_name = :name,
                team_orgid = :orgid
            WHERE team_id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':orgid', $orgid);
        $req->bindParam(':id', $id, PDO::PARAM_INT);

        return $req->execute();
    }

    /**
     * Delete a team on if all the stats are at zero
     *
     * @param int $id ID of the team
     * @return bool true if success, false if the team is not brand new
     */
    public function destroy($id)
    {
        // check for stats, should be 0
        $count = $this->getStats($id);

        if ($count['totxp'] === '0' && $count['totdb'] === '0' && $count['totusers'] === '0') {

            $sql = "DELETE FROM teams WHERE team_id = :team_id";
            $req = $this->Db->prepare($sql);
            $req->bindParam(':team_id', $id, PDO::PARAM_INT);
            $result1 = $req->execute();

            $sql = "DELETE FROM status WHERE team = :team_id";
            $req = $this->Db->prepare($sql);
            $req->bindParam(':team_id', $id, PDO::PARAM_INT);
            $result2 = $req->execute();

            $sql = "DELETE FROM items_types WHERE team = :team_id";
            $req = $this->Db->prepare($sql);
            $req->bindParam(':team_id', $id, PDO::PARAM_INT);
            $result3 = $req->execute();

            $sql = "DELETE FROM experiments_templates WHERE team = :team_id";
            $req = $this->Db->prepare($sql);
            $req->bindParam(':team_id', $id, PDO::PARAM_INT);
            $result4 = $req->execute();

            return $result1 && $result2 && $result3 && $result4;
        }

        return false;
    }

    /**
     * Not implemented
     *
     */
    public function destroyAll()
    {
    }

    /**
     * Clear the timestamp password
     *
     * @return bool
     */
    public function destroyStamppass()
    {
        $sql = "UPDATE teams SET stamppass = NULL WHERE team_id = :team_id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team_id', $this->Users->userData['team']);

        return $req->execute();
    }

    /**
     * Get statistics for the whole install
     *
     * @return array
     */
    public function getAllStats()
    {
        $sql = "SELECT
        (SELECT COUNT(users.userid) FROM users) AS totusers,
        (SELECT COUNT(items.id) FROM items) AS totdb,
        (SELECT COUNT(teams.team_id) FROM teams) AS totteams,
        (SELECT COUNT(experiments.id) FROM experiments) AS totxp,
        (SELECT COUNT(experiments.id) FROM experiments WHERE experiments.timestamped = 1) AS totxpts";
        $req = $this->Db->prepare($sql);
        $req->execute();

        return $req->fetch(PDO::FETCH_NAMED);
    }

    /**
     * Get statistics for a team
     *
     * @param int $team
     * @return array
     */
    public function getStats($team)
    {
        $sql = "SELECT
        (SELECT COUNT(users.userid) FROM users WHERE users.team = :team) AS totusers,
        (SELECT COUNT(items.id) FROM items WHERE items.team = :team) AS totdb,
        (SELECT COUNT(experiments.id) FROM experiments WHERE experiments.team = :team) AS totxp,
        (SELECT COUNT(experiments.id) FROM experiments
            WHERE experiments.team = :team AND experiments.timestamped = 1) AS totxpts";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        $req->execute();

        return $req->fetch(PDO::FETCH_NAMED);
    }
}
