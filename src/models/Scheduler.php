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

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Traits\EntityTrait;
use PDO;

/**
 * All about the team's scheduler
 */
class Scheduler
{
    use EntityTrait;

    /** @var Database $Database instance of Database */
    public $Database;

    /**
     * Constructor
     *
     * @param Database $database
     */
    public function __construct(Database $database)
    {
        $this->Db = Db::getConnection();
        $this->Database = $database;
    }

    /**
     * Add an event for an item in the team
     *
     * @param string $start 2016-07-22T13:37:00
     * @param string $end 2016-07-22T19:42:00
     * @param string $title the comment entered by user
     * @return void
     */
    public function create(string $start, string $end, string $title): void
    {
        $title = filter_var($title, FILTER_SANITIZE_STRING);

        $sql = "INSERT INTO team_events(team, item, start, end, userid, title)
            VALUES(:team, :item, :start, :end, :userid, :title)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Database->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':item', $this->Database->id, PDO::PARAM_INT);
        $req->bindParam(':start', $start);
        $req->bindParam(':end', $end);
        $req->bindParam(':title', $title);
        $req->bindParam(':userid', $this->Database->Users->userData['userid'], PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Return an array with events for this item
     *
     * @return array
     */
    public function read(): array
    {
        // the title of the event is Firstname + Lastname of the user who booked it
        $sql = "SELECT team_events.*,
            CONCAT(team_events.title, ' (', u.firstname, ' ', u.lastname, ')') AS title
            FROM team_events
            LEFT JOIN users AS u ON team_events.userid = u.userid
            WHERE team_events.team = :team AND team_events.item = :item";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Database->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':item', $this->Database->id, PDO::PARAM_INT);
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
     * Read info from an event id
     *
     * @return array
     */
    public function readFromId(): array
    {
        $sql = "SELECT * from team_events WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        return $req->fetch();
    }

    /**
     * Update the start (and end) of an event (when you drag and drop it)
     *
     * @param string $start 2016-07-22T13:37:00
     * @param string $end 2016-07-22T13:37:00
     * @return void
     */
    public function updateStart(string $start, string $end): void
    {
        $sql = "UPDATE team_events SET start = :start, end = :end WHERE team = :team AND id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':start', $start);
        $req->bindParam(':end', $end);
        $req->bindParam(':team', $this->Database->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Update the end of an event (when you resize it)
     *
     * @param string $end 2016-07-22T13:37:00
     * @return void
     */
    public function updateEnd(string $end): void
    {
        $sql = "UPDATE team_events SET end = :end WHERE team = :team AND id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':end', $end);
        $req->bindParam(':team', $this->Database->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Remove an event
     *
     * @return void
     */
    public function destroy(): void
    {
        $sql = "DELETE FROM team_events WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }
}
