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

use function array_map;
use function array_merge;
use function array_unique;
use function count;
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

    /** Admin role never bypasses the resource-specific approver list. */
    public function canApprove(array $item, int $userid): bool
    {
        return (int) $item['requires_booking_approval'] === 1
            && in_array($userid, $this->getApprovers($item), true);
    }

    public function initialStatus(array $item, int $userid): BookingApprovalStatus
    {
        if ((int) $item['requires_booking_approval'] !== 1) {
            return BookingApprovalStatus::NoApproval;
        }
        $approvers = $this->getApprovers($item);
        if (!$approvers) {
            throw new ImproperActionException('No active approver is configured for this resource.');
        }
        return in_array($userid, $approvers, true)
            ? BookingApprovalStatus::Approved
            : BookingApprovalStatus::Pending;
    }

    /** Active designated approvers belonging to the resource team. */
    public function getApprovers(array $item): array
    {
        $req = $this->Db->prepare('SELECT a.userid FROM items_booking_approvers AS a
            INNER JOIN users2teams AS ut ON (ut.users_id = a.userid AND ut.teams_id = :team AND ut.is_archived = 0)
            INNER JOIN users AS u ON (u.userid = a.userid AND u.validated = 1
                AND (u.valid_until IS NULL OR u.valid_until > NOW()))
            WHERE a.entity_id = :item ORDER BY a.userid');
        $req->bindValue(':item', $item['id'], PDO::PARAM_INT);
        $req->bindValue(':team', $item['team'], PDO::PARAM_INT);
        $this->Db->execute($req);
        return array_map('intval', $req->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Configure booking approval within the resource PATCH transaction. */
    public function configure(Items $Item, array $params): void
    {
        $team = (int) $Item->entityData['team'];
        $helper = new TeamsHelper($team);
        $requiresApproval = filter_var($params['requires_booking_approval'] ?? null, FILTER_VALIDATE_INT);
        if ($requiresApproval !== 0 && $requiresApproval !== 1) {
            throw new ImproperActionException('Invalid requires_booking_approval value.');
        }
        if (!isset($params['approvers']) || !is_array($params['approvers'])) {
            throw new ImproperActionException('Expected an array of approver user IDs.');
        }
        $approvers = array();
        foreach ($params['approvers'] as $id) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1 || !$helper->isActiveUserInTeam($id)) {
                throw new ImproperActionException('All approvers must be active members of the resource team.');
            }
            $approvers[$id] = $id;
        }
        if ($requiresApproval === 1 && !$approvers) {
            throw new ImproperActionException('Select at least one approver.');
        }

        $req = $this->Db->prepare('SELECT requires_booking_approval FROM items WHERE id = :id AND team = :team FOR UPDATE');
        $req->bindValue(':id', $Item->id, PDO::PARAM_INT);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $this->Db->execute($req);
        $previousRequired = $req->fetchColumn();
        if ($previousRequired === false) {
            throw new ImproperActionException('Resource not found.');
        }
        $oldApprovers = (int) $previousRequired === 1 && $requiresApproval === 0
            ? $this->getApprovers($Item->entityData) : array();
        $req = $this->Db->prepare('UPDATE items SET requires_booking_approval = :required WHERE id = :id AND team = :team');
        $req->bindValue(':required', $requiresApproval, PDO::PARAM_INT);
        $req->bindValue(':id', $Item->id, PDO::PARAM_INT);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $this->Db->execute($req);
        $req = $this->Db->prepare('DELETE FROM items_booking_approvers WHERE entity_id = :item');
        $req->bindValue(':item', $Item->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $req = $this->Db->prepare('INSERT INTO items_booking_approvers(entity_id, userid) VALUES(:item, :userid)');
        foreach ($approvers as $userid) {
            $req->bindValue(':item', $Item->id, PDO::PARAM_INT);
            $req->bindValue(':userid', $userid, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
        // Agreement: disabling approval explicitly approves pending requests.
        // Resolve recipients before changing statuses, and enqueue inside this transaction.
        if ((int) $previousRequired === 1 && $requiresApproval === 0) {
            $req = $this->Db->prepare('SELECT * FROM team_events WHERE item = :item AND approval_status = :pending ORDER BY start');
            $req->bindValue(':item', $Item->id, PDO::PARAM_INT);
            $req->bindValue(':pending', BookingApprovalStatus::Pending->value, PDO::PARAM_INT);
            $this->Db->execute($req);
            $pendingEvents = $req->fetchAll(PDO::FETCH_ASSOC);
            $req = $this->Db->prepare('UPDATE team_events SET approval_status = :approved, approved_at = NOW(), approved_by = :actor WHERE item = :item AND approval_status = :pending');
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
                $this->finalizeRequest($Item->entityData, $pendingEvent, BookingApprovalStatus::Approved, null, $oldApprovers);
            }
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
            $sql = 'UPDATE team_events SET approval_status = :status, approved_by = :actor, approved_at = NOW(), approval_comment = :comment WHERE item = :item AND approval_status = :pending AND ';
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
        $data = array(
            'request_key' => $recurrenceId ?? 'event:' . $eventId,
            'item_title' => $item['title'],
            'requester_fullname' => $this->actor->userData['fullname'],
            'start' => $occurrences[0]['start'],
            'end' => $occurrences[0]['end'],
            'occurrences' => count($occurrences),
            'status' => 'pending',
        );
        foreach ($this->getApprovers($item) as $approverId) {
            (new BookingApprovalRequested(new Users($approverId), $data))->create();
        }
        (new BookingApprovalUpdated($this->actor, $data))->create();
    }

    /** Notify booker and all current approvers; suppress undelivered obsolete requests. */
    private function finalizeRequest(array $item, array $event, BookingApprovalStatus $status, ?string $comment, ?array $notifyApprovers = null): void
    {
        $key = $this->getRequestKey($event);
        $req = $this->Db->prepare('UPDATE notifications SET send_email = 0, is_ack = 1 WHERE category = :category AND email_sent = 0 AND body->>"$.request_key" = :request_key');
        $req->bindValue(':category', Notifications::BookingApprovalRequested->value, PDO::PARAM_INT);
        $req->bindValue(':request_key', $key);
        $this->Db->execute($req);
        $data = array(
            'item_title' => $item['title'],
            'start' => $event['start'],
            'end' => $event['end'],
            'status' => $status === BookingApprovalStatus::Approved ? 'approved' : 'rejected',
            'comment' => $comment,
        );
        $recipients = array_unique(array_merge(array((int) $event['userid']), $notifyApprovers ?? $this->getApprovers($item)));
        foreach ($recipients as $userid) {
            $data['tab'] = (int) $userid === (int) $event['userid'] ? 2 : 3;
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
}
