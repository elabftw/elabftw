<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @author Moustapha / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\AuditEvent\BookingApprovalDecision;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\BookingApprovalStatus;
use Elabftw\Enums\Notifications;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\Items;
use Elabftw\Models\Notifications\BookingApprovalRequested;
use Elabftw\Models\Notifications\BookingApprovalUpdated;
use Elabftw\Models\Users\Users;
use PDO;
use Throwable;

use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function date;
use function filter_var;
use function in_array;
use function is_array;

/** Resource booking approval rules. Administrators do not bypass configured approvers. */
final class BookingApproval
{
    private Db $Db;

    public function __construct(private readonly Users $actor)
    {
        $this->Db = Db::getConnection();
    }

    /** Admin role never bypasses the resource-specific validator list. */
    public function canApprove(array $item, int $userid): bool
    {
        if ((int) $item['requires_validation'] !== 1) {
            return false;
        }
        $helper = new TeamsHelper((int) $item['team']);
        return $helper->isActiveUserInTeam($userid)
            && $this->isDesignatedUser((int) $item['id'], $userid);
    }

    public function initialStatus(array $item, int $userid): BookingApprovalStatus
    {
        if ((int) $item['requires_validation'] !== 1) {
            return BookingApprovalStatus::Approved;
        }
        $validators = $this->getValidators($item);
        if (!$validators) {
            throw new ImproperActionException('No active validator is configured for this resource.');
        }
        return in_array($userid, $validators, true)
            ? BookingApprovalStatus::Approved
            : BookingApprovalStatus::Pending;
    }

    public function getDesignatedUserIds(int $itemId): array
    {
        $req = $this->Db->prepare('SELECT userid FROM items_booking_validators WHERE item_id = :item ORDER BY userid');
        $req->bindValue(':item', $itemId, PDO::PARAM_INT);
        $this->Db->execute($req);
        return array_map('intval', $req->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Active designated validators belonging to the resource team. */
    public function getValidators(array $item): array
    {
        $ids = $this->getDesignatedUserIds((int) $item['id']);
        $helper = new TeamsHelper((int) $item['team']);
        return array_values(array_filter($ids, static fn(int $id): bool => $helper->isActiveUserInTeam($id)));
    }

    /** PATCH items/{id}: {action:'update', target:'book_approval', requires_validation, userids}. */
    public function configure(Items $Item, array $params): void
    {
        if ($Item->id === null) {
            throw new ImproperActionException('A resource is required.');
        }
        $team = (int) $Item->entityData['team'];
        $helper = new TeamsHelper($team);
        // Only the resource owner or an admin of its team may configure approval.
        if ((int) $Item->entityData['userid'] !== (int) $this->actor->userid
            && !$helper->isAdminInTeam((int) $this->actor->userid)) {
            throw new ForbiddenException();
        }
        $requiresValidation = filter_var($params['requires_validation'] ?? null, FILTER_VALIDATE_INT);
        if ($requiresValidation !== 0 && $requiresValidation !== 1) {
            throw new ImproperActionException('Invalid requires_validation value.');
        }
        if (!isset($params['userids']) || !is_array($params['userids'])) {
            throw new ImproperActionException('Expected an array of validator user IDs.');
        }
        $users = array();
        foreach ($params['userids'] as $id) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1 || !$helper->isActiveUserInTeam($id)) {
                throw new ImproperActionException('All validators must be active members of the resource team.');
            }
            $users[$id] = $id;
        }
        if ($requiresValidation === 1 && !$users) {
            throw new ImproperActionException('Select at least one validator.');
        }

        $this->Db->beginTransaction();
        try {
            $req = $this->Db->prepare('SELECT requires_validation FROM items WHERE id = :id AND team = :team FOR UPDATE');
            $req->bindValue(':id', $Item->id, PDO::PARAM_INT);
            $req->bindValue(':team', $team, PDO::PARAM_INT);
            $this->Db->execute($req);
            $previousRequired = $req->fetchColumn();
            if ($previousRequired === false) {
                throw new ImproperActionException('Resource not found.');
            }
            $oldValidators = $this->getValidators($Item->entityData);
            $req = $this->Db->prepare('UPDATE items SET requires_validation = :required WHERE id = :id AND team = :team');
            $req->bindValue(':required', $requiresValidation, PDO::PARAM_INT);
            $req->bindValue(':id', $Item->id, PDO::PARAM_INT);
            $req->bindValue(':team', $team, PDO::PARAM_INT);
            $this->Db->execute($req);
            $req = $this->Db->prepare('DELETE FROM items_booking_validators WHERE item_id = :item');
            $req->bindValue(':item', $Item->id, PDO::PARAM_INT);
            $this->Db->execute($req);
            $req = $this->Db->prepare('INSERT INTO items_booking_validators(item_id, userid) VALUES(:item, :userid)');
            foreach ($users as $userid) {
                $req->bindValue(':item', $Item->id, PDO::PARAM_INT);
                $req->bindValue(':userid', $userid, PDO::PARAM_INT);
                $this->Db->execute($req);
            }
            // Agreement: disabling approval explicitly approves pending requests.
            // Resolve recipients before changing statuses, and enqueue inside this transaction.
            if ((int) $previousRequired === 1 && $requiresValidation === 0) {
                $req = $this->Db->prepare('SELECT * FROM team_events WHERE item = :item AND approval_status = :pending ORDER BY start');
                $req->bindValue(':item', $Item->id, PDO::PARAM_INT);
                $req->bindValue(':pending', BookingApprovalStatus::Pending->value, PDO::PARAM_INT);
                $this->Db->execute($req);
                $pendingEvents = $req->fetchAll(PDO::FETCH_ASSOC);
                $req = $this->Db->prepare('UPDATE team_events SET approval_status = :approved, approval_decided_at = NOW(), approval_decided_by = :actor WHERE item = :item AND approval_status = :pending');
                $req->bindValue(':approved', BookingApprovalStatus::Approved->value, PDO::PARAM_INT);
                $req->bindValue(':pending', BookingApprovalStatus::Pending->value, PDO::PARAM_INT);
                $req->bindValue(':actor', $this->actor->userid, PDO::PARAM_INT);
                $req->bindValue(':item', $Item->id, PDO::PARAM_INT);
                $this->Db->execute($req);
                $notified = array();
                foreach ($pendingEvents as $pendingEvent) {
                    $key = $this->getRequestKey($pendingEvent);
                    if (isset($notified[$key])) {
                        continue;
                    }
                    $notified[$key] = true;
                    $this->finalizeRequest($Item->entityData, $pendingEvent, BookingApprovalStatus::Approved, null, $oldValidators);
                }
            }
            $this->Db->commit();
        } catch (Throwable $e) {
            $this->Db->rollback();
            throw $e;
        }
    }

    /** POST/PATCH decision for one booking or its entire recurrence. */
    public function decide(int $eventId, string $decision, ?string $comment = null): void
    {
        $status = match ($decision) {
            'approved' => BookingApprovalStatus::Approved,
            'rejected' => BookingApprovalStatus::Rejected,
            default => throw new ImproperActionException('Invalid booking decision.'),
        };
        $req = $this->Db->prepare('SELECT item FROM team_events WHERE id = :id');
        $req->bindValue(':id', $eventId, PDO::PARAM_INT);
        $this->Db->execute($req);
        $itemId = $req->fetchColumn();
        if ($itemId === false) {
            throw new ImproperActionException('Reservation not found.');
        }
        $this->Db->beginTransaction();
        try {
            $req = $this->Db->prepare('SELECT * FROM items WHERE id = :item FOR UPDATE');
            $req->bindValue(':item', $itemId, PDO::PARAM_INT);
            $this->Db->execute($req);
            $item = $req->fetch(PDO::FETCH_ASSOC);
            if (!$item || !$this->canApprove($item, (int) $this->actor->userid)) {
                throw new ForbiddenException();
            }
            $req = $this->Db->prepare('SELECT * FROM team_events WHERE id = :id AND item = :item FOR UPDATE');
            $req->bindValue(':id', $eventId, PDO::PARAM_INT);
            $req->bindValue(':item', $itemId, PDO::PARAM_INT);
            $this->Db->execute($req);
            $event = $req->fetch(PDO::FETCH_ASSOC);
            if (!$event || (int) $event['approval_status'] !== BookingApprovalStatus::Pending->value) {
                throw new ImproperActionException('This booking request is no longer pending.');
            }
            $recurrenceId = $event['recurrence_id'];
            $sql = 'SELECT * FROM team_events WHERE item = :item AND approval_status = :pending AND ';
            $sql .= $recurrenceId === null ? 'id = :id' : 'recurrence_id = :recurrence';
            $req = $this->Db->prepare($sql);
            $req->bindValue(':item', $itemId, PDO::PARAM_INT);
            $req->bindValue(':pending', BookingApprovalStatus::Pending->value, PDO::PARAM_INT);
            if ($recurrenceId === null) {
                $req->bindValue(':id', $eventId, PDO::PARAM_INT);
            } else {
                $req->bindValue(':recurrence', $recurrenceId);
            }
            $this->Db->execute($req);
            $events = $req->fetchAll(PDO::FETCH_ASSOC);
            if ($status === BookingApprovalStatus::Approved) {
                foreach ($events as $candidate) {
                    if ($candidate['start'] <= date('Y-m-d H:i:s')) {
                        throw new ImproperActionException('A booking cannot be approved after it starts.');
                    }
                }
            }
            $sql = 'UPDATE team_events SET approval_status = :status, approval_decided_by = :actor, approval_decided_at = NOW(), approval_comment = :comment WHERE item = :item AND approval_status = :pending AND ';
            $sql .= $recurrenceId === null ? 'id = :id' : 'recurrence_id = :recurrence';
            $req = $this->Db->prepare($sql);
            $req->bindValue(':status', $status->value, PDO::PARAM_INT);
            $req->bindValue(':actor', $this->actor->userid, PDO::PARAM_INT);
            $req->bindValue(':comment', $comment, $comment === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $req->bindValue(':item', $itemId, PDO::PARAM_INT);
            $req->bindValue(':pending', BookingApprovalStatus::Pending->value, PDO::PARAM_INT);
            if ($recurrenceId === null) {
                $req->bindValue(':id', $eventId, PDO::PARAM_INT);
            } else {
                $req->bindValue(':recurrence', $recurrenceId);
            }
            $this->Db->execute($req);
            $this->finalizeRequest($item, $event, $status, $comment);
            $this->Db->commit();
        } catch (Throwable $e) {
            $this->Db->rollback();
            throw $e;
        }
    }

    /** Called by Scheduler after a pending booking is inserted, before transaction commit. */
    public function notifySubmission(array $item, int $eventId, array $occurrences, ?string $recurrenceId): void
    {
        $user = $this->actor;
        $data = array(
            'request_key' => $recurrenceId ?? 'event:' . $eventId,
            'event_id' => $eventId,
            'item_id' => (int) $item['id'],
            'item_title' => $item['title'],
            'requester_fullname' => $user->userData['fullname'],
            'start' => $occurrences[0]['start'],
            'end' => $occurrences[0]['end'],
            'occurrences' => count($occurrences),
            'status' => 'pending',
            'comment' => null,
        );
        foreach ($this->getValidators($item) as $validatorId) {
            (new BookingApprovalRequested(new Users($validatorId), $data))->create();
        }
        (new BookingApprovalUpdated(new Users((int) $user->userid), $data))->create();
    }

    /** Notify booker and all current approvers; suppress undelivered obsolete requests. */
    private function finalizeRequest(array $item, array $event, BookingApprovalStatus $status, ?string $comment, ?array $notifyValidators = null): void
    {
        $key = $this->getRequestKey($event);
        $req = $this->Db->prepare('UPDATE notifications SET send_email = 0, is_ack = 1 WHERE category = :category AND email_sent = 0 AND body->>"$.request_key" = :request_key');
        $req->bindValue(':category', Notifications::BookingApprovalRequested->value, PDO::PARAM_INT);
        $req->bindValue(':request_key', $key);
        $this->Db->execute($req);
        $owner = new Users((int) $event['userid']);
        $data = array(
            'request_key' => $key,
            'event_id' => (int) $event['id'],
            'item_id' => (int) $event['item'],
            'item_title' => $item['title'],
            'requester_fullname' => $owner->userData['fullname'],
            'start' => $event['start'],
            'end' => $event['end'],
            'occurrences' => 1,
            'status' => match ($status) {
                BookingApprovalStatus::Approved => 'approved',
                BookingApprovalStatus::Rejected => 'rejected',
                default => 'pending',
            },
            'comment' => $comment,
        );
        $recipients = array_unique(array_merge(array((int) $event['userid']), $notifyValidators ?? $this->getValidators($item)));
        foreach ($recipients as $userid) {
            (new BookingApprovalUpdated(new Users($userid), $data))->create();
        }
        AuditLogs::create(new BookingApprovalDecision(
            (int) $this->actor->userid,
            (int) $event['userid'],
            (int) $event['id'],
            $data['status'],
            $event['recurrence_id'],
        ));
    }

    private function getRequestKey(array $event): string
    {
        return $event['recurrence_id'] ?? ('event:' . $event['id']);
    }

    private function isDesignatedUser(int $itemId, int $userid): bool
    {
        $req = $this->Db->prepare('SELECT 1 FROM items_booking_validators WHERE item_id = :item AND userid = :userid');
        $req->bindValue(':item', $itemId, PDO::PARAM_INT);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchColumn() !== false;
    }
}
