<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use DateTime;
use Elabftw\Elabftw\CreateNotificationParams;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\TeamsHelper;
use Elabftw\Traits\EntityTrait;
use function filter_var;
use function in_array;
use PDO;
use function preg_replace;
use function strlen;
use function substr;

/**
 * All about the team's scheduler
 */
class Scheduler
{
    use EntityTrait;

    public function __construct(public Items $Items)
    {
        $this->Db = Db::getConnection();
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

        $start = $this->normalizeDate($start);
        $end = $this->normalizeDate($end, true);

        // users won't be able to create an entry in the past
        $this->isFutureOrExplode(DateTime::createFromFormat(DateTime::ISO8601, $start));

        // fix booking at midnight on monday not working. See #2765
        // we add a second so it works
        $start = preg_replace('/00:00:00/', '00:00:01', $start);

        $sql = 'INSERT INTO team_events(team, item, start, end, userid, title)
            VALUES(:team, :item, :start, :end, :userid, :title)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Items->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':item', $this->Items->id, PDO::PARAM_INT);
        $req->bindParam(':start', $start);
        $req->bindParam(':end', $end);
        $req->bindParam(':title', $title);
        $req->bindParam(':userid', $this->Items->Users->userData['userid'], PDO::PARAM_INT);
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
        $start = $this->normalizeDate($start);
        $end = $this->normalizeDate($end, true);

        // the title of the event is title + Firstname Lastname of the user who booked it
        $sql = "SELECT team_events.title, team_events.id, team_events.start, team_events.end, team_events.userid,
            CONCAT('[', items.title, '] ', team_events.title, ' (', u.firstname, ' ', u.lastname, ')') AS title,
            items.title AS item_title,
            CONCAT('#', items_types.color) AS color,
            CONCAT(u.firstname, ' ', u.lastname) AS fullname
            FROM team_events
            LEFT JOIN items ON team_events.item = items.id
            LEFT JOIN items_types ON items.category = items_types.id
            LEFT JOIN users AS u ON team_events.userid = u.userid
            WHERE team_events.team = :team
            AND team_events.start > :start AND team_events.end <= :end";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Items->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':start', $start);
        $req->bindParam(':end', $end);
        $this->Db->execute($req);

        return $req->fetchAll();
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
        $req->bindParam(':item', $this->Items->id, PDO::PARAM_INT);
        $req->bindParam(':start', $start);
        $req->bindParam(':end', $end);
        $this->Db->execute($req);

        return $req->fetchAll();
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
        return $this->Db->fetch($req);
    }

    /**
     * Update the start (and end) of an event (when you drag and drop it)
     *
     * @param array<string, string> $delta timedelta
     */
    public function updateStart(array $delta): void
    {
        $this->canWriteOrExplode();
        $event = $this->readFromId();
        $oldStart = DateTime::createFromFormat(DateTime::ISO8601, $event['start']);
        $oldEnd = DateTime::createFromFormat(DateTime::ISO8601, $event['end']);
        $seconds = '0';
        if (strlen($delta['milliseconds']) > 3) {
            $seconds = substr($delta['milliseconds'], 0, -3);
        }
        $newStart = $oldStart->modify('+' . $delta['days'] . ' day')->modify('+' . $seconds . ' seconds'); // @phpstan-ignore-line
        $this->isFutureOrExplode($newStart);
        $newEnd = $oldEnd->modify('+' . $delta['days'] . ' day')->modify('+' . $seconds . ' seconds'); // @phpstan-ignore-line
        $this->isFutureOrExplode($newEnd);

        $sql = 'UPDATE team_events SET start = :start, end = :end WHERE team = :team AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':start', $newStart->format('c'));
        $req->bindValue(':end', $newEnd->format('c'));
        $req->bindParam(':team', $this->Items->Users->userData['team'], PDO::PARAM_INT);
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
        $this->canWriteOrExplode();
        $event = $this->readFromId();
        $oldEnd = DateTime::createFromFormat(DateTime::ISO8601, $event['end']);
        $seconds = '0';
        if (strlen($delta['milliseconds']) > 3) {
            $seconds = substr($delta['milliseconds'], 0, -3);
        }
        $newEnd = $oldEnd->modify('+' . $delta['days'] . ' day')->modify('+' . $seconds . ' seconds'); // @phpstan-ignore-line
        $this->isFutureOrExplode($newEnd);

        $sql = 'UPDATE team_events SET end = :end WHERE team = :team AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':end', $newEnd->format('c'));
        $req->bindParam(':team', $this->Items->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Bind an entity to a calendar event
     */
    public function bind(int $entityid, string $type): bool
    {
        $this->canWriteOrExplode();
        $this->validateBindType($type);

        $sql = 'UPDATE team_events SET ' . $type . ' = :entity WHERE team = :team AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity', $entityid, PDO::PARAM_INT);
        $req->bindParam(':team', $this->Items->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Unbind an entity from a calendar event
     */
    public function unbind(string $type): bool
    {
        $this->canWriteOrExplode();
        $this->validateBindType($type);

        $sql = 'UPDATE team_events SET ' . $type . ' = NULL WHERE team = :team AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Items->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Remove an event
     */
    public function destroy(): bool
    {
        $this->canWriteOrExplode();
        $sql = 'DELETE FROM team_events WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        // send a notification to all team admins
        $TeamsHelper = new TeamsHelper($this->Items->Users->userData['team']);
        $Notifications = new Notifications($this->Items->Users);
        $Notifications->createMultiUsers(
            new CreateNotificationParams(
                Notifications::EVENT_DELETED,
                array('event' => $this->readFromId(), 'actor' => $this->Items->Users->userData['fullname']),
            ),
            $TeamsHelper->getAllAdminsUserid(),
            (int) $this->Items->Users->userData['userid'],
        );
        return $this->Db->execute($req);
    }

    /**
     * Check that the date is in the future
     * Unlike Admins, Users can't create/modify something in the past
     * Input can be false because DateTime::createFromFormat will return false on failure
     */
    private function isFutureOrExplode(DateTime|false $date): void
    {
        if ($date === false) {
            throw new ImproperActionException('Could not understand date format!');
        }
        if ($this->Items->Users->userData['is_admin']) {
            return;
        }
        $now = new DateTime();
        if ($now > $date) {
            throw new ImproperActionException(_('Creation/modification of events in the past is not allowed!'));
        }
    }

    /**
     * Date can be Y-m-d or ISO::8601
     * Make sure we have the time, too
     */
    private function normalizeDate(string $date, bool $rmDay = false): string
    {
        if (DateTime::createFromFormat(DateTime::ISO8601, $date) === false) {
            $dateOnly = DateTime::createFromFormat('Y-m-d', $date);
            if ($dateOnly === false) {
                throw new ImproperActionException('Could not understand date format!');
            }
            $dateOnly->setTime(0, 1);
            // we don't want the end date to go over one day
            if ($rmDay) {
                $dateOnly->modify('-3min');
            }
            return $dateOnly->format(DateTime::ISO8601);
        }
        return $date;
    }

    /**
     * Check if current logged in user can edit an event
     * Only admins can edit events from someone else
     */
    private function canWrite(): bool
    {
        $event = $this->readFromId();
        // make sure we are not modifying something in the past if we're not admin
        $this->isFutureOrExplode(DateTime::createFromFormat(DateTime::ISO8601, $event['start']));
        // if it's our event (and it's not in the past) we can write to it for sure
        if ($event['userid'] === $this->Items->Users->userData['userid']) {
            return true;
        }

        // if it's not, we need to be admin in the same team as the event/user
        $TeamsHelper = new TeamsHelper((int) $event['team']);
        if ($TeamsHelper->isUserInTeam((int) $this->Items->Users->userData['userid']) &&
           (int) $this->Items->Users->userData['usergroup'] <= 2) {
            return true;
        }

        return false;
    }

    private function canWriteOrExplode(): void
    {
        if ($this->canWrite() === false) {
            throw new ImproperActionException(Tools::error(true));
        }
    }

    private function validateBindType(string $type): void
    {
        $allowedTypes = array('experiment', 'item_link');
        if (!in_array($type, $allowedTypes, true)) {
            throw new IllegalActionException('Incorrect bind type for scheduler event');
        }
    }
}
