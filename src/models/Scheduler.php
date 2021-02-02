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

use DateTime;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Traits\EntityTrait;
use PDO;
use function strlen;
use function substr;

/**
 * All about the team's scheduler
 */
class Scheduler
{
    use EntityTrait;

    public Database $Database;

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
     * @return int the new id
     */
    public function create(string $start, string $end, string $title): int
    {
        $title = filter_var($title, FILTER_SANITIZE_STRING);

        $sql = 'INSERT INTO team_events(team, item, start, end, userid, title)
            VALUES(:team, :item, :start, :end, :userid, :title)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Database->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':item', $this->Database->id, PDO::PARAM_INT);
        $req->bindParam(':start', $start);
        $req->bindParam(':end', $end);
        $req->bindParam(':title', $title);
        $req->bindParam(':userid', $this->Database->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Return an array with events for all items of the team
     *
     * @param string $start 2019-12-23T00:00:00 01:00
     * @param string $end 2019-12-30T00:00:00 01:00
     */
    public function readAllFromTeam(string $start, string $end): array
    {
        // the title of the event is title + Firstname Lastname of the user who booked it
        $sql = "SELECT team_events.title, team_events.id, team_events.start, team_events.end, team_events.userid,
            CONCAT('[', items.title, '] ', team_events.title, ' (', u.firstname, ' ', u.lastname, ')') AS title,
            items.title AS item_title,
            CONCAT('#', items_types.color) AS color
            FROM team_events
            LEFT JOIN items ON team_events.item = items.id
            LEFT JOIN items_types ON items.category = items_types.id
            LEFT JOIN users AS u ON team_events.userid = u.userid
            WHERE team_events.team = :team
            AND team_events.start > :start AND team_events.end <= :end";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Database->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':start', $start);
        $req->bindParam(':end', $end);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Return an array with events for this item
     *
     * @param string $start 2019-12-23T00:00:00 01:00
     * @param string $end 2019-12-30T00:00:00 01:00
     */
    public function read(string $start, string $end): array
    {
        // the title of the event is title + Firstname Lastname of the user who booked it
        // the color is used by fullcalendar for the bg color of the event
        $sql = "SELECT team_events.*,
            CONCAT(team_events.title, ' (', u.firstname, ' ', u.lastname, ') ', COALESCE(experiments.title, '')) AS title,
            CONCAT('#', items_types.color) AS color
            FROM team_events
            LEFT JOIN items ON team_events.item = items.id
            LEFT JOIN experiments ON (experiments.id = team_events.experiment)
            LEFT JOIN items_types ON items.category = items_types.id
            LEFT JOIN users AS u ON team_events.userid = u.userid
            WHERE team_events.item = :item
            AND team_events.start > :start AND team_events.end <= :end";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item', $this->Database->id, PDO::PARAM_INT);
        $req->bindParam(':start', $start);
        $req->bindParam(':end', $end);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Read info from an event id
     */
    public function readFromId(): array
    {
        $sql = 'SELECT * from team_events WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetch();
        if ($res === false) {
            throw new ResourceNotFoundException();
        }

        return $res;
    }

    /**
     * Update the start (and end) of an event (when you drag and drop it)
     *
     * @param array<string, string> $delta timedelta
     */
    public function updateStart(array $delta): void
    {
        $event = $this->readFromId();
        $oldStart = DateTime::createFromFormat(DateTime::ISO8601, $event['start']);
        $oldEnd = DateTime::createFromFormat(DateTime::ISO8601, $event['end']);
        $seconds = '0';
        if (strlen($delta['milliseconds']) > 3) {
            $seconds = substr($delta['milliseconds'], 0, -3);
        }
        $newStart = $oldStart->modify('+' . $delta['days'] . ' day')->modify('+' . $seconds . ' seconds'); // @phpstan-ignore-line
        $newEnd = $oldEnd->modify('+' . $delta['days'] . ' day')->modify('+' . $seconds . ' seconds'); // @phpstan-ignore-line

        $sql = 'UPDATE team_events SET start = :start, end = :end WHERE team = :team AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':start', $newStart->format('c'));
        $req->bindValue(':end', $newEnd->format('c'));
        $req->bindParam(':team', $this->Database->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Update the end of an event (when you resize it)
     *
     * @param array<string, string> $delta timedelta
     */
    public function updateEnd(array $delta): void
    {
        $event = $this->readFromId();
        $oldEnd = DateTime::createFromFormat(DateTime::ISO8601, $event['end']);
        $seconds = '0';
        if (strlen($delta['milliseconds']) > 3) {
            $seconds = substr($delta['milliseconds'], 0, -3);
        }
        $newEnd = $oldEnd->modify('+' . $delta['days'] . ' day')->modify('+' . $seconds . ' seconds'); // @phpstan-ignore-line

        $sql = 'UPDATE team_events SET end = :end WHERE team = :team AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':end', $newEnd->format('c'));
        $req->bindParam(':team', $this->Database->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Bind an experiment to a calendar event
     *
     * @param int $expid id of the experiment
     */
    public function bind(int $expid): void
    {
        $sql = 'UPDATE team_events SET experiment = :experiment WHERE team = :team AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':experiment', $expid, PDO::PARAM_INT);
        $req->bindParam(':team', $this->Database->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Unbind an experiment from a calendar event
     */
    public function unbind(): void
    {
        $sql = 'UPDATE team_events SET experiment = NULL WHERE team = :team AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Database->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Remove an event
     */
    public function destroy(): void
    {
        // check permission before deleting
        $event = $this->readFromId();
        // if the user is not the same, check if we are admin
        // admin and sysadmin will have usergroup of 1 or 2
        if ($event['userid'] !== $this->Database->Users->userData['userid'] && (int) $this->Database->Users->userData['usergroup'] <= 2) {
            // check user is in our team
            $Booker = new Users((int) $event['userid']);
            if ($Booker->userData['team'] !== $this->Database->Users->userData['team']) {
                throw new ImproperActionException(Tools::error(true));
            }
        }
        $sql = 'DELETE FROM team_events WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}
