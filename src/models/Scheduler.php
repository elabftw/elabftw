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
use DateTimeImmutable;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Models\Notifications\EventDeleted;
use Elabftw\Services\Filter;
use Elabftw\Services\TeamsHelper;
use Elabftw\Traits\EntityTrait;
use PDO;

use function array_walk;
use function preg_replace;
use function strlen;
use function substr;

/**
 * All about the team's scheduler
 */
class Scheduler implements RestInterface
{
    use EntityTrait;

    public const string EVENT_START = '2012-31-12T00:00:00+00:00';

    public const string EVENT_END = '2037-31-12T00:00:00+00:00';

    private string $start = self::EVENT_START;

    private string $end = self::EVENT_END;

    public function __construct(
        public Items $Items,
        ?int $id = null,
        ?string $start = null,
        ?string $end = null,
        private ?int $category = null,
    ) {
        $this->Db = Db::getConnection();
        $this->setId($id);
        if ($start !== null) {
            $this->start = $start;
        }
        if ($end !== null) {
            $this->end = $end;
        }
    }

    public function getApiPath(): string
    {
        // We don't use team.php?item= because the id will be the id of the event upon creation
        return 'api/v2/event/';
    }

    /**
     * Add an event for an item in the team
     * No other action than Create
     * Date format: 2016-07-22T13:37:00+02:00
     * reqBody :
     * - ?title
     * - start
     * - end
     */
    public function postAction(Action $action, array $reqBody): int
    {
        if ($this->Items->id === null) {
            throw new ImproperActionException('An item id is needed.');
        }
        if (!$this->Items->canBook()) {
            throw new ImproperActionException(_('You do not have the permission to book this entry.'));
        }
        $start = $this->normalizeDate($reqBody['start']);
        $end = $this->normalizeDate($reqBody['end'], true);
        $this->checkConstraints($start, $end);
        $this->checkMaxSlots();

        // users won't be able to create an entry in the past
        $this->isFutureOrExplode(DateTime::createFromFormat(DateTime::ATOM, $start));

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
        $req->bindValue(':title', $this->filterTitle($reqBody['title']));
        $req->bindParam(':userid', $this->Items->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Read info from an event id or read the events from an item
     * The api controller doesn't know what kind of read we want
     */
    public function readOne(): array
    {
        if ($this->id !== null) {
            return $this->readOneEvent();
        }
        return $this->read();
    }

    /**
     * Return an array with events for all items of the team
     */
    public function readAll(): array
    {
        // the title of the event is title + Firstname Lastname of the user who booked it
        $sql = sprintf(
            "SELECT
                team_events.id,
                team_events.title AS title_only,
                team_events.start,
                team_events.end,
                team_events.userid,
                TIMESTAMPDIFF(MINUTE, team_events.start, team_events.end) AS event_duration_minutes,
                CONCAT(u.firstname, ' ', u.lastname) AS fullname,
                CONCAT('[', items.title, '] ', team_events.title, ' (', u.firstname, ' ', u.lastname, ')') AS title,
                items.title AS item_title,
                items.book_is_cancellable,
                CONCAT('#', items_types.color) AS color,
                team_events.experiment,
                items.category AS items_category,
                items.id AS items_id,
                experiments.title AS experiment_title,
                team_events.item_link,
                items_linkt.title AS item_link_title
            FROM team_events
            LEFT JOIN experiments ON (team_events.experiment = experiments.id)
            LEFT JOIN items ON (team_events.item = items.id)
            LEFT JOIN items AS items_linkt ON (team_events.item_link = items_linkt.id)
            LEFT JOIN items_types ON (items.category = items_types.id)
            LEFT JOIN users AS u ON (team_events.userid = u.userid)
            WHERE (team_events.team = :team OR items.team = :team)
                --                 |start  search range  end|
                -- | event 1 | | event 2 | | event 3 | | event 4 | | event 5 |
                --               |           event 6          |
                -- events.start <= range.end and events.end >= range.start
                AND team_events.start <= :end
                AND team_events.end >= :start
                %s",
            $this->category > 0
                ? 'AND items.category = :category'
                : ''
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Items->Users->userData['team'], PDO::PARAM_INT);
        $req->bindValue(':start', $this->normalizeDate($this->start));
        $req->bindValue(':end', $this->normalizeDate($this->end, true));
        if ($this->category > 0) {
            $req->bindParam(':category', $this->category);
        }
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function patch(Action $action, array $params): array
    {
        $this->canWriteOrExplode();

        match ($params['target']) {
            'start' => $this->updateStart($params['delta']),
            'end' => $this->updateEnd($params['delta']),
            'experiment' => $this->bind('experiment', $params['id']),
            'item_link' => $this->bind('item_link', $params['id']),
            'title' => $this->updateTitle($params['content']),
            'start_epoch' => $this->updateEpoch('start', $params['epoch']),
            'end_epoch' => $this->updateEpoch('end', $params['epoch']),
            default => throw new ImproperActionException('Incorrect target parameter.'),
        };
        return $this->readOne();
    }

    /**
     * Remove an event
     */
    public function destroy(): bool
    {
        $this->canWriteOrExplode();
        $event = $this->readOne();
        if ($event['book_is_cancellable'] === 0 && !$this->Items->Users->isAdmin) {
            throw new ImproperActionException(_('Event cancellation is not permitted.'));
        }
        if ($event['book_cancel_minutes'] !== 0 && !$this->Items->Users->isAdmin) {
            $now = new DateTimeImmutable();
            $eventStart = new DateTimeImmutable($event['start']);
            $interval = $now->diff($eventStart);
            $totalMinutes = ($interval->h * 60) + $interval->i;
            if ($totalMinutes < $event['book_cancel_minutes']) {
                throw new ImproperActionException(sprintf(_('Cannot cancel slot less than %d minutes before its start.'), $event['book_cancel_minutes']));
            }
        }
        $sql = 'DELETE FROM team_events WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        // send a notification to all team admins
        $TeamsHelper = new TeamsHelper($this->Items->Users->userData['team']);
        $Notif = new EventDeleted($this->readOne(), $this->Items->Users->userData['fullname']);
        $admins = $TeamsHelper->getAllAdminsUserid();
        array_walk($admins, function ($userid) use ($Notif) {
            if ($userid === $this->Items->Users->userData['userid']) {
                return;
            }
            $Notif->create($userid);
        });
        return $this->Db->execute($req);
    }

    /**
     * Return an array with events for this item
     */
    private function read(): array
    {
        // the title of the event is title + Firstname Lastname of the user who booked it
        // the color is used by fullcalendar for the bg color of the event
        $sql = "SELECT team_events.*,
            CONCAT(team_events.title, ' (', u.firstname, ' ', u.lastname, ') ', COALESCE(experiments.title, '')) AS title,
            team_events.title AS title_only,
            CONCAT('#', items_types.color) AS color,
            experiments.title AS experiment_title,
            items_linkt.title AS item_link_title,
            items.title AS item_title, items.book_is_cancellable
            FROM team_events
            LEFT JOIN items ON (team_events.item = items.id)
            LEFT JOIN items AS items_linkt ON (team_events.item_link = items_linkt.id)
            LEFT JOIN experiments ON (experiments.id = team_events.experiment)
            LEFT JOIN items_types ON (items.category = items_types.id)
            LEFT JOIN users AS u ON team_events.userid = u.userid
            WHERE team_events.item = :item
                --                 |start  search range  end|
                -- | event 1 | | event 2 | | event 3 | | event 4 | | event 5 |
                --               |           event 6          |
                -- events.start <= range.end and events.end >= range.start
                AND team_events.start <= :end
                AND team_events.end >= :start";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item', $this->Items->id, PDO::PARAM_INT);
        $req->bindValue(':start', $this->normalizeDate($this->start));
        $req->bindValue(':end', $this->normalizeDate($this->end, true));
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Use a direct target date in Unix time format (from the modal) instead of a delta (from the calendar)
     * The column is passed by the app, not the user.
     */
    private function updateEpoch(string $column, string $epoch): bool
    {
        $event = $this->readOne();
        $this->checkConstraints($event['start'], $event['end']);
        $new = DateTimeImmutable::createFromFormat('U', $epoch);
        if ($new === false) {
            throw new ImproperActionException('Invalid date format received.');
        }
        $this->isFutureOrExplode($new);
        $sql = 'UPDATE team_events SET ' . $column . ' = :new WHERE id = :id';
        $req = $this->Db->prepare($sql);
        // don't use 'c' here but a custom construct so the timezone is correctly registered
        $req->bindValue(':new', $new->format('Y-m-d\TH:i:s') . date('P'));
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    // the title (comment) can be an empty string
    private function filterTitle(?string $title): string
    {
        $filteredTitle = '';
        if (!empty($title)) {
            $filteredTitle = Filter::title($title);
        }
        return $filteredTitle;
    }

    private function readOneEvent(): array
    {
        $sql = 'SELECT
                team_events.id,
                team_events.team,
                team_events.item,
                team_events.start,
                team_events.end,
                team_events.title,
                team_events.userid,
                team_events.experiment,
                team_events.item_link,
                items.book_is_cancellable,
                items.book_cancel_minutes,
                team_events.title AS title_only,
                experiments.title AS experiment_title,
                items_linkt.title AS item_link_title
            FROM team_events
            LEFT JOIN items ON (team_events.item = items.id)
            LEFT JOIN experiments ON (experiments.id = team_events.experiment)
            LEFT JOIN items AS items_linkt ON (team_events.item_link = items_linkt.id)
            WHERE team_events.id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $event = $this->Db->fetch($req);
        $this->Items->setId($event['item']);
        return $event;
    }

    /**
     * Update the start (and end) of an event (when you drag and drop it)
     *
     * @param array<string, string> $delta timedelta
     */
    private function updateStart(array $delta): bool
    {
        $event = $this->readOne();
        $oldStart = DateTime::createFromFormat(DateTime::ATOM, $event['start']);
        $oldEnd = DateTime::createFromFormat(DateTime::ATOM, $event['end']);
        $seconds = '0';
        if (strlen((string) $delta['milliseconds']) > 3) {
            $seconds = substr((string) $delta['milliseconds'], 0, -3);
        }
        $newStart = $oldStart->modify($delta['days'] . ' day')->modify($seconds . ' seconds'); // @phpstan-ignore-line
        $this->isFutureOrExplode($newStart);
        $newEnd = $oldEnd->modify($delta['days'] . ' day')->modify($seconds . ' seconds'); // @phpstan-ignore-line
        $this->isFutureOrExplode($newEnd);
        $this->checkConstraints($newStart->format(DateTime::ATOM), $newEnd->format(DateTime::ATOM));

        $sql = 'UPDATE team_events SET start = :start, end = :end WHERE team = :team AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':start', $newStart->format('c'));
        $req->bindValue(':end', $newEnd->format('c'));
        $req->bindParam(':team', $this->Items->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Update the end of an event (when you resize it)
     *
     * @param array<string, string> $delta timedelta
     */
    private function updateEnd(array $delta): bool
    {
        $event = $this->readOne();
        $oldEnd = DateTime::createFromFormat(DateTime::ATOM, $event['end']);
        $seconds = '0';
        if (strlen((string) $delta['milliseconds']) > 3) {
            $seconds = substr((string) $delta['milliseconds'], 0, -3);
        }
        $newEnd = $oldEnd->modify($delta['days'] . ' day')->modify($seconds . ' seconds'); // @phpstan-ignore-line
        $this->isFutureOrExplode($newEnd);
        $this->checkConstraints($event['start'], $newEnd->format(DateTime::ATOM));

        $sql = 'UPDATE team_events SET end = :end WHERE team = :team AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':end', $newEnd->format('c'));
        $req->bindParam(':team', $this->Items->Users->userData['team'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function updateTitle(string $title): bool
    {
        $sql = 'UPDATE team_events SET title = :title WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindValue(':title', $this->filterTitle($title));
        return $this->Db->execute($req);
    }

    /**
     * Bind an entity to a calendar event
     * Note: the column is set here, not taken from request
     * and the entityId can only be int so no need to validate it
     */
    private function bind(string $column, ?int $entityid = null): bool
    {
        $sql = 'UPDATE team_events SET ' . $column . ' = :entity WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity', $entityid, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function checkSlotTime(string $start, string $end): void
    {
        if ($this->Items->entityData['book_max_minutes'] === 0) {
            return;
        }
        $start = new DateTimeImmutable($start);
        $end = new DateTimeImmutable($end);
        $interval = $start->diff($end);
        $totalMinutes = ($interval->h * 60) + $interval->i;
        if ($totalMinutes > $this->Items->entityData['book_max_minutes']) {
            throw new ImproperActionException(sprintf(_('Slot time is limited to %d minutes.'), $this->Items->entityData['book_max_minutes']));
        }
    }

    private function checkMaxSlots(): void
    {
        if ($this->Items->entityData['book_max_slots'] === 0) {
            return;
        }
        $sql = 'SELECT count(id) FROM team_events WHERE start > NOW() AND item = :item AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':item', $this->Items->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Items->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $count = $req->fetchColumn();
        if ($count >= $this->Items->entityData['book_max_slots']) {
            throw new ImproperActionException(
                sprintf(_('You cannot book any more slots. Maximum of %d reached.'), $this->Items->entityData['book_max_slots'])
            );
        }
    }

    private function checkConstraints(string $start, string $end): void
    {
        $this->checkOverlap($start, $end);
        $this->checkSlotTime($start, $end);
    }

    /**
     * Look if another slot is present for the same item at the same time and throw exception if yes
     */
    private function checkOverlap(string $start, string $end): void
    {
        if ($this->Items->entityData['book_can_overlap'] === 1) {
            return;
        }
        $sql = 'SELECT id FROM team_events WHERE :start < end AND :end > start AND item = :item';
        if ($this->id !== null) {
            $sql .= ' AND id != :id';
        }
        $req = $this->Db->prepare($sql);
        $req->bindParam(':start', $start);
        $req->bindParam(':end', $end);
        $req->bindParam(':item', $this->Items->id, PDO::PARAM_INT);
        if ($this->id !== null) {
            $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        }
        $this->Db->execute($req);
        if (!empty($req->fetchAll())) {
            throw new ImproperActionException(_('Overlapping booking slots is not permitted.'));
        }
    }

    /**
     * Check that the date is in the future
     * Unlike Admins, Users can't create/modify something in the past, unless book_users_can_in_past is truthy
     * Input can be false because DateTime::createFromFormat will return false on failure
     */
    private function isFutureOrExplode(DateTime|DateTimeImmutable|false $date): void
    {
        if ($this->Items->canBookInPast()) {
            return;
        }
        if ($date === false) {
            throw new ImproperActionException('Could not understand date format!');
        }
        $now = new DateTime();
        if ($now > $date) {
            throw new ImproperActionException(_('Creation/modification of events in the past is not allowed!'));
        }
    }

    /**
     * Date can be Y-m-d or ISO::ATOM
     * Make sure we have the time, too
     */
    private function normalizeDate(string $date, bool $rmDay = false): string
    {
        if (DateTime::createFromFormat(DateTime::ATOM, $date) === false) {
            $dateOnly = DateTime::createFromFormat('Y-m-d', $date);
            if ($dateOnly === false) {
                throw new ImproperActionException('Could not understand date format!');
            }
            $dateOnly->setTime(0, 1);
            // we don't want the end date to go over one day
            if ($rmDay) {
                $dateOnly->modify('-3min');
            }
            return $dateOnly->format(DateTime::ATOM);
        }
        return $date;
    }

    /**
     * Check if current logged in user can edit an event
     * Only admins can edit events from someone else
     */
    private function canWrite(): bool
    {
        $event = $this->readOne();
        // if it's our event (and it's not in the past) we can write to it for sure
        if ($event['userid'] === $this->Items->Users->userData['userid']) {
            return true;
        }

        // if it's not, we need to be admin in the same team as the event/user
        $TeamsHelper = new TeamsHelper($event['team']);
        return $TeamsHelper->isAdminInTeam($this->Items->Users->userData['userid']);
    }

    private function canWriteOrExplode(): void
    {
        if ($this->canWrite() === false) {
            throw new ImproperActionException(Tools::error(true));
        }
    }
}
