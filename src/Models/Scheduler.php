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
use Elabftw\Elabftw\EntitySqlBuilder;
use Elabftw\Enums\Action;
use Elabftw\Enums\Scope;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Notifications\EventDeleted;
use Elabftw\Services\Filter;
use Elabftw\Services\TeamsHelper;
use Elabftw\Traits\EntityTrait;
use Override;
use PDO;

use function array_walk;
use function preg_replace;
use function strlen;
use function mb_substr;

/**
 * All about the team's scheduler
 */
final class Scheduler extends AbstractRest
{
    use EntityTrait;

    public const string EVENT_START = '2012-31-12T00:00:00+00:00';

    public const string EVENT_END = '2037-31-12T00:00:00+00:00';

    private const int GRACE_PERIOD_MINUTES = 5;

    public Items $Items;

    private string $start = self::EVENT_START;

    private string $end = self::EVENT_END;

    private array $filterSqlParts = array();

    private array $filterBindings = array();

    public function __construct(
        AbstractEntity $Items,
        ?int $id = null,
        ?string $start = null,
        ?string $end = null,
    ) {
        if (!$Items instanceof Items) {
            throw new ImproperActionException('Scheduler can only work with resources (items).');
        }
        $this->Items = $Items;
        parent::__construct();
        $this->setId($id);
        if ($start !== null) {
            $this->start = $start;
        }
        if ($end !== null) {
            $this->end = $end;
        }
    }

    #[Override]
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
    #[Override]
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
        $req->bindValue(':title', $this->filterTitle($reqBody['title'] ?? ''));
        $req->bindParam(':userid', $this->Items->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Read info from an event id or read the events from an item
     * The api controller doesn't know what kind of read we want
     */
    #[Override]
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
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        // prepare filters for the scheduler view
        if ($queryParams !== null) {
            $this->appendFilterSql(column: 'items.category', paramName: 'category', value: $queryParams->getQuery()->getInt('category'));
            $this->appendFilterSql(column: 'team_events.userid', paramName: 'ownerid', value: $queryParams->getQuery()->getInt('eventOwner'));
            // handle multiple items
            $itemParams = $queryParams->getQuery()->all('items');
            $ids = array_filter(array_map('intval', $itemParams)); // force numeric IDs
            $this->appendItemsIdsToSql($ids);
        }
        // apply scope for events
        $itemParams = $queryParams?->getQuery()->all('items');
        $itemId = !empty($itemParams) ? (int) $itemParams[0] : 0;
        $scopeInt = $this->getScope($itemId);
        if ($scopeInt === Scope::User->value) {
            $this->appendFilterSql('team_events.userid', 'userid', $this->Items->Users->userData['userid']);
        } elseif ($scopeInt === Scope::Team->value) {
            $this->appendFilterSql('team_events.team', 'team', $this->Items->Users->userData['team']);
        }

        $builder = new EntitySqlBuilder($this->Items);
        $this->filterSqlParts[] = str_replace('entity.', 'items.', $builder->getCanFilter('canread'));
        $this->filterBindings['userid'] = $this->Items->Users->userData['userid']; // needed for :userid in builder SQL
        $this->filterBindings['team'] = $this->Items->Users->userData['team']; // same

        // 'canbook' boolean to display events that user can read but not book
        $canBookFilter = str_replace('entity.', 'items.', $builder->getCanFilter('canbook'));
        $canBookExpr = trim(preg_replace('/^\s*AND\s*/', '', $canBookFilter, 1) ?? '');
        if ($canBookExpr === '') {
            $canBookExpr = '0';
        }

        // the title of the event is title + Firstname Lastname of the user who booked it
        $sql = sprintf(
            "SELECT
                team_events.id,
                team_events.team,
                team_events.title AS title_only,
                team_events.start,
                team_events.end,
                team_events.userid,
                team_events.created_at,
                team_events.modified_at,
                TIMESTAMPDIFF(MINUTE, team_events.start, team_events.end) AS event_duration_minutes,
                CONCAT(u.firstname, ' ', u.lastname) AS fullname,
                CONCAT('[', items.title, '] ', team_events.title, ' (', u.firstname, ' ', u.lastname, ')') AS title,
                items.title AS item_title,
                items.book_is_cancellable,
                CONCAT('#', items_categories.color) AS color,
                team_events.experiment,
                items.category AS items_category,
                items.id AS items_id,
                experiments.title AS experiment_title,
                team_events.item_link,
                items_linkt.title AS item_link_title,
                CASE WHEN %s THEN 1 ELSE 0 END AS canbook
            FROM team_events
            LEFT JOIN experiments ON (team_events.experiment = experiments.id)
            LEFT JOIN items ON (team_events.item = items.id)
            LEFT JOIN items AS items_linkt ON (team_events.item_link = items_linkt.id)
            LEFT JOIN items_categories ON (items.category = items_categories.id)
            LEFT JOIN users AS u ON (team_events.userid = u.userid)
            LEFT JOIN users2teams ON (users2teams.users_id = items.userid AND users2teams.teams_id = :team)
            WHERE 1 = 1
                AND team_events.start <= :end
                AND team_events.end >= :start
                %s",
            $canBookExpr,
            implode(' ', $this->filterSqlParts)
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':start', $this->normalizeDate($this->start));
        $req->bindValue(':end', $this->normalizeDate($this->end, true));
        foreach ($this->filterBindings as $param => $value) {
            $req->bindValue(":$param", $value, PDO::PARAM_INT);
        }
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    #[Override]
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
    #[Override]
    public function destroy(): bool
    {
        $this->canWriteOrExplode();
        $event = $this->readOne();
        $createdAt = new DateTimeImmutable($event['created_at']);
        $start = new DateTimeImmutable($event['start']);
        $this->isEditableOrExplode($createdAt, $start);

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

    private function appendItemsIdsToSql(array $itemsIds): void
    {
        if (empty($itemsIds)) {
            return;
        }
        $placeholders = array();
        foreach ($itemsIds as $index => $id) {
            $key = "itemid$index";
            $placeholders[] = ":$key";
            $this->filterBindings[$key] = $id;
        }
        $this->filterSqlParts[] = 'AND items.id IN (' . implode(',', $placeholders) . ')';
    }

    private function getScope(int $queryParamsItem): int
    {
        // if there is an item selected, we force the events scope to everything
        if ($queryParamsItem > 0) {
            return Scope::Everything->value;
        }
        return $this->Items->Users->userData['scope_events'] ?? Scope::Everything->value;
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
            CONCAT('#', items_categories.color) AS color,
            experiments.title AS experiment_title,
            items_linkt.title AS item_link_title,
            items.title AS item_title, items.book_is_cancellable
            FROM team_events
            LEFT JOIN items ON (team_events.item = items.id)
            LEFT JOIN items AS items_linkt ON (team_events.item_link = items_linkt.id)
            LEFT JOIN experiments ON (experiments.id = team_events.experiment)
            LEFT JOIN items_categories ON (items.category = items_categories.id)
            LEFT JOIN users AS u ON team_events.userid = u.userid
            WHERE team_events.item = :item
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
    private function filterTitle(string $title): string
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
                team_events.created_at,
                team_events.modified_at,
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
            $seconds = mb_substr((string) $delta['milliseconds'], 0, -3);
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
            $seconds = mb_substr((string) $delta['milliseconds'], 0, -3);
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
        $totalMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
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
     * Check that the item has been created in the last minutes (GRACE_PERIOD_MINUTES)
     * Users can't delete events in the past (see #5596) unless in this span of time.
     */
    private function isInGracePeriod(DateTimeImmutable $createdAt): bool
    {
        $now = new DateTimeImmutable();
        $gracePeriodEnd = $createdAt->modify(sprintf('+%d minutes', self::GRACE_PERIOD_MINUTES));
        return $now <= $gracePeriodEnd;
    }

    private function isEditableOrExplode(DateTimeImmutable $createdAt, DateTimeImmutable $startDate): void
    {
        if ($this->Items->Users->isAdmin) {
            return;
        }
        if ($this->isInGracePeriod($createdAt)) {
            return;
        }
        $this->isFutureOrExplode($startDate);
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
            throw new IllegalActionException();
        }
    }

    private function appendFilterSql(string $column, string $paramName, int $value): void
    {
        if ($value <= 0 || isset($this->filterBindings[$paramName])) {
            return;
        }
        $this->filterSqlParts[] = sprintf('AND %s = :%s', $column, $paramName);
        $this->filterBindings[$paramName] = $value;
    }
}
