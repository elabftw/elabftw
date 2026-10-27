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
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\Items;
use Elabftw\Models\Notifications\BookingApprovalRequested;
use Elabftw\Models\Notifications\BookingApprovalUpdated;
use Elabftw\Models\Users\Users;
use Elabftw\Params\EntityParams;
use Elabftw\Params\Guard;
use PDO;
use Throwable;

use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function in_array;

/** Resource booking approval rules. Administrators do not bypass configured approvers. */
final class BookingApproval
{
    private Db $Db;

    public function __construct(private readonly Users $actor)
    {
        $this->Db = Db::getConnection();
    }

    /** Check whether a user is a designated approver for a resource. */
    public function canApprove(int $itemId, int $userid): bool
    {
        return in_array($userid, $this->getApprovers($itemId), true);
    }

    /** Determine the status assigned when a new booking is created. */
    public function initialStatus(int $itemId, bool $requiresApproval, int $userid): BookingApprovalStatus
    {
        if (!$requiresApproval) {
            return BookingApprovalStatus::NoApproval;
        }
        return $this->canApprove($itemId, $userid)
            ? BookingApprovalStatus::Approved
            : BookingApprovalStatus::Pending;
    }

    /** Return the list of users designated as approvers for a resource */
    public function getApprovers(int $itemId): array
    {
        $req = $this->Db->prepare('SELECT userid FROM items_booking_approvers WHERE entity_id = :item ORDER BY userid');
        $req->bindValue(':item', $itemId, PDO::PARAM_INT);
        $this->Db->execute($req);
        return array_map('intval', $req->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Update the approval requirement and replace the selected approvers. */
    public function updateSettings(Items $Item, bool $requiresApproval, array $approvers): void
    {
        if ($requiresApproval) {
            Guard::ensureArrayNotEmpty('approvers', $approvers);
            $this->replaceApprovers($Item, array_unique($approvers));
        }
        if ((bool) $Item->entityData['requires_booking_approval'] !== $requiresApproval) {
            $Item->update(new EntityParams('requires_booking_approval', $requiresApproval));
        }
    }

    /** Approve or reject a pending booking and its recurrence, if any. */
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
            if (!$item || !$this->canApprove((int) $itemId, (int) $this->actor->userid)) {
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

    /** Notify approvers and the requester when a booking is submitted for approval. */
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
        foreach ($this->getApprovers((int) $item['id']) as $approverId) {
            new BookingApprovalRequested(new Users($approverId), $data)->create();
        }
        new BookingApprovalUpdated($this->actor, $data)->create();
    }

    /** Replace the stored approver list with the selected user IDs. */
    private function replaceApprovers(Items $Item, array $approvers): void
    {
        $this->removeApprovers($Item);
        $this->addApprovers($Item, $approvers);
    }

    /** Remove all approver for the resource. */
    private function removeApprovers(Items $Item): void
    {
        $req = $this->Db->prepare('DELETE FROM items_booking_approvers WHERE entity_id = :item');
        $req->bindValue(':item', $Item->id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /** Store the selected approver for the resource. */
    private function addApprovers(Items $Item, array $approvers): void
    {
        $req = $this->Db->prepare('INSERT INTO items_booking_approvers(entity_id, userid) VALUES(:item, :userid)');
        $req->bindValue(':item', $Item->id, PDO::PARAM_INT);
        foreach ($approvers as $userid) {
            $req->bindValue(':userid', $userid, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    /** Notify the requester and current approvers of a decision and record its audit event. */
    private function finalizeRequest(array $item, array $event, BookingApprovalStatus $status, ?string $comment): void
    {
        $data = array(
            'item_title' => $item['title'],
            'start' => $event['start'],
            'end' => $event['end'],
            'status' => $status === BookingApprovalStatus::Approved ? 'approved' : 'rejected',
            'comment' => $comment,
        );
        $recipients = array_unique(array_merge(array((int) $event['userid']), $this->getApprovers((int) $item['id'])));
        foreach ($recipients as $userid) {
            $data['tab'] = (int) $userid === (int) $event['userid'] ? 2 : 3;
            new BookingApprovalUpdated(new Users($userid), $data)->create();
        }
        AuditLogs::create(new BookingApprovalDecision(
            (int) $this->actor->userid,
            (int) $event['userid'],
            (int) $event['id'],
            $data['status'],
            $event['recurrence_id'],
        ));
    }
}
