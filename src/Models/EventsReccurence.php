<?php

/**
 * @author Moustapha Camara
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\EventScope;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Override;
use PDO;
use Throwable;

use function _;
use function array_filter;
use function array_key_exists;
use function array_values;
use function in_array;
use function sprintf;

/**
 * REST operations applying to multiple occurrences of a recurring event.
 */
final class EventsReccurence extends AbstractRest
{
    private const string DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly Scheduler $Scheduler)
    {
        parent::__construct();
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf(
            '%s%d/recurrence/',
            $this->Scheduler->getApiPath(),
            $this->Scheduler->id ?? 0,
        );
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $scope = EventScope::tryFrom((string) ($params['scope'] ?? EventScope::Event->value))
            ?? throw new ImproperActionException(_('Incorrect recurrence scope.'));
        if ($scope === EventScope::Event) {
            return $this->Scheduler->patch($action, $params);
        }

        $event = $this->Scheduler->readOne();
        $recurrenceId = $event['recurrence_id'];
        if ($recurrenceId === null) {
            $params['scope'] = EventScope::Event->value;
            return $this->Scheduler->patch($action, $params);
        }
        if (!in_array($params['target'] ?? '', array('title', 'datetime'), true)) {
            throw new ImproperActionException(_('Recurring scope is only supported for title and datetime updates.'));
        }
        $newTitle = array_key_exists('title', $params)
            ? $this->Scheduler->filterTitle((string) $params['title'])
            : null;
        $changeDateTime = array_key_exists('start', $params) || array_key_exists('end', $params);
        if ($newTitle === null && !$changeDateTime) {
            return $this->Scheduler->readOne();
        }
        if ($changeDateTime && !isset($params['start'], $params['end'])) {
            throw new ImproperActionException('Start and end must both be provided.');
        }

        $this->Db->beginTransaction();
        try {
            $this->Scheduler->lockItemForBooking();
            // Re-read the bookings after locking the resource so validation uses the latest data.
            $event = $this->Scheduler->readOne();
            $recurrenceId = $event['recurrence_id'];
            if ($recurrenceId === null) {
                throw new ImproperActionException(_('This reservation no longer belongs to a recurrence.'));
            }
            $allEvents = $this->readRecurrenceEvents($recurrenceId);
            $this->assertRecurrenceOwnership($allEvents, $event);
            $events = $this->getRecurrenceEventsForScope($allEvents, $event, $scope);
            $candidates = array();
            if ($changeDateTime) {
                $requestedStart = $this->Scheduler->formatDate($this->Scheduler->normalizeDate($params['start']));
                $requestedEnd = $this->Scheduler->formatDate($this->Scheduler->normalizeDate($params['end'], true));
                $this->Scheduler->checkEndAfterStart(
                    $requestedStart->format(self::DATETIME_FORMAT),
                    $requestedEnd->format(self::DATETIME_FORMAT),
                );
                $startDelta = $this->Scheduler->formatDate($event['start'])->diff($requestedStart);
                $duration = $requestedStart->diff($requestedEnd);
                // Shift each selected occurrence by the same offset and apply the requested duration.
                foreach ($events as $recurrenceEvent) {
                    $candidateStart = $this->Scheduler->formatDate($recurrenceEvent['start'])->add($startDelta);
                    $candidateEnd = $candidateStart->add($duration);
                    $candidates[] = array(
                        'id' => $recurrenceEvent['id'],
                        'start' => $this->Scheduler->adjustMidnight($candidateStart->format(self::DATETIME_FORMAT)),
                        'end' => $candidateEnd->format(self::DATETIME_FORMAT),
                    );
                }
            }
            foreach ($candidates as $candidate) {
                $this->Scheduler->isFutureOrExplode($this->Scheduler->formatDate($candidate['start']));
                $this->Scheduler->isFutureOrExplode($this->Scheduler->formatDate($candidate['end']));
                $this->Scheduler->checkConstraints($candidate['start'], $candidate['end'], $recurrenceId, true);
            }
            if ($changeDateTime) {
                $overlapCandidates = $candidates;
                // Add untouched earlier occurrences back so self-overlaps are detected for future-only updates.
                if ($scope === EventScope::Future) {
                    foreach ($allEvents as $recurrenceEvent) {
                        if ((int) $recurrenceEvent['recurrence_index'] >= (int) $event['recurrence_index']) {
                            continue;
                        }
                        $overlapCandidates[] = array(
                            'start' => $recurrenceEvent['start'],
                            'end' => $recurrenceEvent['end'],
                        );
                    }
                }
                $this->Scheduler->checkCandidateOverlaps($overlapCandidates);
            }

            $sql = 'UPDATE team_events SET '
                . ($newTitle !== null ? 'title = :title' : '')
                . ($newTitle !== null && $changeDateTime ? ', ' : '')
                . ($changeDateTime ? 'start = :start, end = :end' : '')
                . ' WHERE team = :team AND id = :id AND recurrence_id = :recurrence_id';
            $req = $this->Db->prepare($sql);
            foreach ($events as $index => $recurrenceEvent) {
                if ($newTitle !== null) {
                    $req->bindValue(':title', $newTitle);
                }
                if ($changeDateTime) {
                    $req->bindValue(':start', $candidates[$index]['start']);
                    $req->bindValue(':end', $candidates[$index]['end']);
                }
                $req->bindValue(':team', $event['team'], PDO::PARAM_INT);
                $req->bindValue(':id', $recurrenceEvent['id'], PDO::PARAM_INT);
                $req->bindValue(':recurrence_id', $recurrenceId);
                $this->Db->execute($req);
            }
            $this->Db->commit();
        } catch (Throwable $e) {
            $this->Db->rollback();
            throw $e;
        }
        return $this->Scheduler->readOne();
    }

    #[Override]
    public function destroy(bool $recursive = false): bool
    {
        $scope = $this->Scheduler->getRecurrenceScope();
        if ($scope === EventScope::Event) {
            return $this->Scheduler->destroy($recursive);
        }

        $this->Db->beginTransaction();
        try {
            $this->Scheduler->lockItemForBooking();
            $event = $this->Scheduler->readOne();
            if ($event['recurrence_id'] === null) {
                throw new ImproperActionException(_('This reservation no longer belongs to a recurrence.'));
            }
            $allEvents = $this->readRecurrenceEvents($event['recurrence_id']);
            $this->assertRecurrenceOwnership($allEvents, $event);
            $events = $this->getRecurrenceEventsForScope($allEvents, $event, $scope);
            foreach ($events as $recurrenceEvent) {
                $this->Scheduler->assertCanDestroy($recurrenceEvent);
            }

            $sql = 'DELETE FROM team_events WHERE team = :team AND recurrence_id = :recurrence_id';
            if ($scope === EventScope::Future) {
                $sql .= ' AND recurrence_index >= :recurrence_index';
            }
            $req = $this->Db->prepare($sql);
            $req->bindValue(':team', $event['team'], PDO::PARAM_INT);
            $req->bindValue(':recurrence_id', $event['recurrence_id']);
            if ($scope === EventScope::Future) {
                $req->bindValue(':recurrence_index', $event['recurrence_index'], PDO::PARAM_INT);
            }
            $result = $this->Db->execute($req);
            // A recurring cancellation produces one administrator notification, not one per occurrence.
            $this->Scheduler->notifyAdminsOfDeletion($event);
            $this->Db->commit();
            return $result;
        } catch (Throwable $e) {
            $this->Db->rollback();
            throw $e;
        }
    }

    private function readRecurrenceEvents(string $recurrenceId): array
    {
        $sql = 'SELECT team_events.*, items.book_is_cancellable, items.book_cancel_minutes
            FROM team_events
            LEFT JOIN items ON (team_events.item = items.id)
            WHERE team_events.recurrence_id = :recurrence_id
            ORDER BY team_events.recurrence_index';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':recurrence_id', $recurrenceId);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    private function getRecurrenceEventsForScope(array $events, array $anchor, EventScope $scope): array
    {
        if ($scope === EventScope::Recurrence) {
            return $events;
        }
        $anchorIndex = (int) $anchor['recurrence_index'];
        return array_values(array_filter(
            $events,
            static fn(array $recurrenceEvent): bool => (int) $recurrenceEvent['recurrence_index'] >= $anchorIndex,
        ));
    }

    private function assertRecurrenceOwnership(array $events, array $anchor): void
    {
        if (empty($events)) {
            throw new ImproperActionException(_('No reservations were found in this recurrence.'));
        }
        foreach ($events as $event) {
            if ($event['team'] !== $anchor['team'] || $event['userid'] !== $anchor['userid'] || $event['item'] !== $anchor['item']) {
                throw new ForbiddenException(_('A recurrence cannot span owners, teams or resources.'));
            }
        }
    }
}
