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
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Services\Filter;
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
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        return (bool) $req->fetch();
    }

    /**
     * Check if the team exists already and create one if not
     *
     * @param string $name Name of the team (case sensitive)
     * @param bool $allowCreate depends on the value of saml_team_create in Config
     * @return int The team ID
     */
    public function initializeIfNeeded(string $name, bool $allowCreate): int
    {
        $sql = 'SELECT id, name, orgid FROM teams';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        $teamsArr = $req->fetchAll();

        if (is_array($teamsArr)) {
            foreach ($teamsArr as $team) {
                if (($team['name'] === $name) || ($team['orgid'] === $name)) {
                    return (int) $team['id'];
                }
            }
        }

        if ($allowCreate) {
            return $this->create($name);
        }
        throw new ImproperActionException('The administrator disabled team creation on SAML login. Contact your administrator for creating the team.');
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
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
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
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

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
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

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

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
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

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
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
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
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

        return $req->execute();
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
        $req->execute();

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
        (SELECT COUNT(users.userid) FROM users WHERE users.team = :team) AS totusers,
        (SELECT COUNT(items.id) FROM items WHERE items.team = :team) AS totdb,
        (SELECT COUNT(experiments.id) FROM experiments WHERE experiments.team = :team) AS totxp,
        (SELECT COUNT(experiments.id) FROM experiments
            WHERE experiments.team = :team AND experiments.timestamped = 1) AS totxpts';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        $req->execute();

        return $req->fetch(PDO::FETCH_NAMED);
    }
}
