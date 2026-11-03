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

namespace Elabftw\Models;

use DateTimeImmutable;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BookingApprovalStatus;
use Elabftw\Enums\Notifications;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Services\BookingApproval;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;

use function array_fill;
use function array_map;
use function implode;
use function json_decode;

final class BookingApprovalTest extends TestCase
{
    use TestsUtilsTrait;

    public function testConfiguredApproverCanApproveButTeamAdminCannotOverride(): void
    {
        [$item, $approver, $admin] = $this->getApprovalFixture();

        $policy = new BookingApproval($approver);
        self::assertTrue($policy->canApprove($item->id, $approver->userid));
        self::assertFalse($policy->canApprove($item->id, $admin->userid));
        self::assertSame(BookingApprovalStatus::Approved, $policy->initialStatus($item->id, (bool) $item->entityData['requires_booking_approval'], $approver->userid));
        self::assertSame(BookingApprovalStatus::Pending, $policy->initialStatus($item->id, (bool) $item->entityData['requires_booking_approval'], $admin->userid));

        $start = new DateTimeImmutable('+3 days 10:00');
        $eventId = (new Scheduler(new Items($admin, $item->id)))->postAction(Action::Create, array(
            'start' => $start->format('c'),
            'end' => $start->modify('+1 hour')->format('c'),
        ));
        $asAdmin = new Scheduler(new Items($admin, $item->id), $eventId);
        self::assertSame(BookingApprovalStatus::Pending->value, (int) $asAdmin->readOne()['approval_status']);

        try {
            $asAdmin->patch(Action::Update, array('target' => 'approval', 'decision' => 'approved'));
            self::fail('A non-designated team admin must not approve a booking.');
        } catch (ForbiddenException) {
            // Expected: admin status is not an approval override.
        }

        $asApprover = new Scheduler(new Items($approver, $item->id), $eventId);
        $asApprover->patch(Action::Update, array('target' => 'approval', 'decision' => 'approved'));
        self::assertSame(BookingApprovalStatus::Approved->value, (int) $asApprover->readOne()['approval_status']);
    }

    public function testPendingRequestBlocksSlotAndDisablingApprovalLeavesItPending(): void
    {
        [$item, $approver, $requester] = $this->getApprovalFixture();
        // approval rules take precedence over overlapping slots being allowed on the resource
        $item->patch(Action::Update, array('book_can_overlap' => 1));
        $start = new DateTimeImmutable('+4 days 10:00');
        $params = array('start' => $start->format('c'), 'end' => $start->modify('+1 hour')->format('c'));
        $scheduler = new Scheduler(new Items($requester, $item->id));
        self::assertSame(1, (int) $scheduler->Items->entityData['book_can_overlap']);
        $eventId = $scheduler->postAction(Action::Create, $params);
        self::assertSame(BookingApprovalStatus::Pending->value, (int) (new Scheduler(new Items($requester, $item->id), $eventId))->readOne()['approval_status']);

        try {
            $scheduler->postAction(Action::Create, $params);
            self::fail('A pending booking should block this slot.');
        } catch (ImproperActionException) {
            // Expected overlap.
        }

        $item->patch(Action::Update, array(
            'requires_booking_approval' => 0,
            'approvers' => array($approver->userid),
        ));
        // use a new variable: repeating the exact expression asserted above makes phpstan consider it already narrowed
        $afterDisable = new Scheduler(new Items($requester, $item->id), $eventId);
        self::assertSame(BookingApprovalStatus::Pending->value, (int) $afterDisable->readOne()['approval_status']);
    }

    public function testTeamMemberWithWriteAccessCanConfigureBookingApproval(): void
    {
        $owner = $this->getUserInTeam(2, admin: 1);
        $writer = $this->getUserInTeam(2);
        $item = $this->getFreshItemWithGivenUser($owner);
        $item->patch(Action::Update, array('canwrite_base' => BasePermissions::Team->value));

        $writerItem = new Items($writer, $item->id);
        $writerItem->patch(Action::Update, array(
            'requires_booking_approval' => 1,
            'approvers' => array($writer->userid),
        ));

        self::assertSame(array((int) $writer->userid), $writerItem->readOne()['approvers']);
        self::assertSame(1, (int) (new Items($owner, $item->id))->entityData['requires_booking_approval']);
    }

    public function testBookingSettingsAndApproversAreSavedInOnePatch(): void
    {
        $item = $this->getFreshBookableItem(2);
        $approver = $this->getUserInTeam(2);
        $item->patch(Action::Update, array(
            'book_max_slots' => 3,
            'requires_booking_approval' => 1,
            'approvers' => array($approver->userid),
        ));

        $updated = new Items($item->Users, $item->id);
        self::assertSame(3, (int) $updated->entityData['book_max_slots']);
        self::assertSame(1, (int) $updated->entityData['requires_booking_approval']);
        self::assertSame(array((int) $approver->userid), $updated->readOne()['approvers']);

        $listed = $updated->readAll($updated->getQueryParams(new InputBag(array('full' => 1, 'extended' => 'id:' . $item->id))));
        self::assertCount(1, $listed);
        self::assertSame(array((int) $approver->userid), $listed[0]['approvers']);
    }

    public function testInvalidBookingSettingsRollBackApproversAndResourceChanges(): void
    {
        $item = $this->getFreshBookableItem(2);
        $approver = $this->getUserInTeam(2);
        $originalSlots = (int) $item->entityData['book_max_slots'];

        try {
            $item->patch(Action::Update, array(
                'book_max_slots' => 3,
                'invalid_setting' => 'x',
                'requires_booking_approval' => 1,
                'approvers' => array($approver->userid),
            ));
            self::fail('The PATCH should reject an invalid resource parameter.');
        } catch (ImproperActionException) {
            // Both kinds of settings must remain unchanged.
        }

        $updated = new Items($item->Users, $item->id);
        self::assertSame($originalSlots, (int) $updated->entityData['book_max_slots']);
        self::assertSame(0, (int) $updated->entityData['requires_booking_approval']);
        self::assertSame(array(), $updated->readOne()['approvers']);
    }

    public function testApprovalRequiredAlsoPreventsOverlappingRecurrenceOccurrences(): void
    {
        $item = $this->getFreshBookableItem(2);
        $approver = $this->getUserInTeam(2);
        $item->patch(Action::Update, array(
            'requires_booking_approval' => 1,
            'approvers' => array($approver->userid),
        ));

        $scheduler = new Scheduler($item);
        $this->expectException(ImproperActionException::class);
        $scheduler->checkCandidateOverlaps(array(
            array('start' => '2027-03-01 10:00:00', 'end' => '2027-03-01 12:00:00'),
            array('start' => '2027-03-01 11:00:00', 'end' => '2027-03-01 13:00:00'),
        ));
    }

    public function testUserWithoutWriteAccessCannotConfigureBookingApproval(): void
    {
        $owner = $this->getUserInTeam(2, admin: 1);
        $otherUser = $this->getUserInTeam(2);
        $item = $this->getFreshItemWithGivenUser($owner);
        $item->patch(Action::Update, array('canwrite_base' => BasePermissions::UserOnly->value));

        $this->expectException(ForbiddenException::class);
        (new Items($otherUser, $item->id))->patch(Action::Update, array(
            'requires_booking_approval' => 1,
            'approvers' => array($otherUser->userid),
        ));
    }

    public function testApproverMayBeOutsideResourceTeamAndUnvalidated(): void
    {
        $item = $this->getFreshBookableItem(2);
        $Db = Db::getConnection();
        $req = $Db->prepare('SELECT u.userid FROM users AS u
            WHERE NOT EXISTS (SELECT 1 FROM users2teams AS ut WHERE ut.users_id = u.userid AND ut.teams_id = :team)
            LIMIT 1');
        $req->bindValue(':team', $item->entityData['team'], PDO::PARAM_INT);
        $Db->execute($req);
        $approverId = $req->fetchColumn();
        self::assertNotFalse($approverId);
        $approverId = (int) $approverId;

        // An unvalidated user outside the resource team may still be designated.
        $req = $Db->prepare('SELECT validated FROM users WHERE userid = :userid');
        $req->bindValue(':userid', $approverId, PDO::PARAM_INT);
        $Db->execute($req);
        $previousValidated = (int) $req->fetchColumn();
        $req = $Db->prepare('UPDATE users SET validated = 0 WHERE userid = :userid');
        $req->bindValue(':userid', $approverId, PDO::PARAM_INT);
        $Db->execute($req);
        try {
            $item->patch(Action::Update, array('requires_booking_approval' => 1, 'approvers' => array($approverId)));
            $approval = new BookingApproval($item->Users);
            self::assertSame(array($approverId), $approval->getApprovers((int) $item->id));
            self::assertTrue($approval->canApprove((int) $item->id, $approverId));
        } finally {
            $req = $Db->prepare('UPDATE users SET validated = :validated WHERE userid = :userid');
            $req->bindValue(':validated', $previousValidated, PDO::PARAM_INT);
            $req->bindValue(':userid', $approverId, PDO::PARAM_INT);
            $Db->execute($req);
        }
    }

    public function testApprovalCannotBeEnabledWithoutApprovers(): void
    {
        $owner = $this->getUserInTeam(2);
        $item = $this->getFreshItemWithGivenUser($owner);
        $this->expectException(ImproperActionException::class);
        $item->patch(Action::Update, array('requires_booking_approval' => 1, 'approvers' => array()));
    }

    public function testApprovalCanBeDisabledWithoutSendingApprovers(): void
    {
        $item = $this->getFreshBookableItem(2);
        $item->patch(Action::Update, array('requires_booking_approval' => '0'));
        self::assertSame(0, (int) new Items($item->Users, $item->id)->entityData['requires_booking_approval']);
    }

    public function testInvalidApproverIdIsRejected(): void
    {
        $item = $this->getFreshBookableItem(2);
        $this->expectException(ImproperActionException::class);
        $item->patch(Action::Update, array('requires_booking_approval' => 1, 'approvers' => array('abc')));
    }

    public function testReadShowKeepsRows(): void
    {
        $item = $this->getFreshBookableItem(2);
        $listed = $item->readAll($item->getQueryParams(new InputBag(array('extended' => 'id:' . $item->id))));
        self::assertCount(1, $listed);
        self::assertIsArray($listed[0]);
        self::assertSame($item->id, (int) $listed[0]['id']);
    }

    public function testPendingRequestBlocksSlot(): void
    {
        $owner = $this->getUserInTeam(2);
        $item = $this->getFreshItemWithGivenUser($owner);
        $approval = new BookingApproval($owner);
        $item->patch(Action::Update, array(
            'requires_booking_approval' => 1,
            'approvers' => array($owner->userid, $owner->userid),
        ));
        self::assertSame(array((int) $owner->userid), $approval->getApprovers((int) $item->id));
    }

    public function testUpdatingApproversReplacesPreviousSelection(): void
    {
        $item = $this->getFreshBookableItem(2);
        $first = $this->getUserInTeam(2);
        $second = $this->getUserInTeam(2, admin: 1);
        $item->patch(Action::Update, array('requires_booking_approval' => 1, 'approvers' => array($first->userid, $second->userid)));
        self::assertEqualsCanonicalizing(array((int) $first->userid, (int) $second->userid), $item->readOne()['approvers']);
        $item->patch(Action::Update, array('requires_booking_approval' => 1, 'approvers' => array($second->userid)));
        self::assertSame(array((int) $second->userid), $item->readOne()['approvers']);
    }

    public function testRejectedBookingRetainsReasonAndFreesSlot(): void
    {
        [$item, $approver, $requester] = $this->getApprovalFixture();
        $start = new DateTimeImmutable('+6 days 10:00');
        $params = array('start' => $start->format('c'), 'end' => $start->modify('+1 hour')->format('c'));
        $scheduler = new Scheduler(new Items($requester, $item->id));
        $id = $scheduler->postAction(Action::Create, $params);

        (new BookingApproval($approver))->decide($id, 'rejected', 'Maintenance');
        $event = (new Scheduler(new Items($requester, $item->id), $id))->readOne();
        self::assertSame(BookingApprovalStatus::Rejected->value, (int) $event['approval_status']);
        self::assertSame('Maintenance', $event['approval_comment']);
        self::assertSame((int) $approver->userid, (int) $event['approved_by']);

        // Rejected requests no longer occupy a slot.
        $replacement = $scheduler->postAction(Action::Create, $params);
        self::assertNotSame($id, $replacement);
    }

    public function testRecurringBookingDecisionAppliesToAllOccurrences(): void
    {
        [$item, $approver, $requester] = $this->getApprovalFixture();
        $start = new DateTimeImmutable('+7 days 10:00');
        $id = (new Scheduler(new Items($requester, $item->id)))->postAction(Action::Create, array(
            'start' => $start->format('c'),
            'end' => $start->modify('+1 hour')->format('c'),
            'recurrence' => array('frequency' => 'daily', 'interval' => 1, 'count' => 3),
        ));

        (new BookingApproval($approver))->decide($id, 'approved');
        $req = Db::getConnection()->prepare('SELECT approval_status FROM team_events WHERE item = :item AND recurrence_id IS NOT NULL');
        $req->bindValue(':item', $item->id, PDO::PARAM_INT);
        Db::getConnection()->execute($req);
        $statuses = array_map('intval', $req->fetchAll(PDO::FETCH_COLUMN));
        self::assertCount(3, $statuses);
        self::assertSame(array_fill(0, 3, BookingApprovalStatus::Approved->value), $statuses);

        // the booking owner is notified once, for the whole recurrence
        $req = Db::getConnection()->prepare('SELECT body FROM notifications WHERE userid = :userid AND category = :category ORDER BY id DESC LIMIT 1');
        $req->bindValue(':userid', $requester->userid, PDO::PARAM_INT);
        $req->bindValue(':category', Notifications::BookingApprovalUpdated->value, PDO::PARAM_INT);
        Db::getConnection()->execute($req);
        $body = json_decode((string) $req->fetchColumn(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('approved', $body['status']);
        self::assertSame(3, $body['occurrences']);
    }

    public function testBookingDecisionRejectsUnknownDecision(): void
    {
        $this->expectException(ImproperActionException::class);
        (new BookingApproval($this->getUserInTeam(2)))->decide(1, 'postponed');
    }

    public function testBookingDecisionIsIdempotentButCannotBeReversed(): void
    {
        [$item, $approver, $requester] = $this->getApprovalFixture();
        $start = new DateTimeImmutable('+8 days 10:00');
        $id = (new Scheduler(new Items($requester, $item->id)))->postAction(Action::Create, array(
            'start' => $start->format('c'),
            'end' => $start->modify('+1 hour')->format('c'),
        ));

        $approval = new BookingApproval($approver);
        $approval->decide($id, 'rejected');
        $approval->decide($id, 'rejected');
        $this->expectException(ImproperActionException::class);
        $approval->decide($id, 'approved');
    }

    public function testOverduePendingBookingCanStillBeApproved(): void
    {
        [$item, $approver, $requester] = $this->getApprovalFixture();
        $start = new DateTimeImmutable('+9 days 10:00');
        $id = (new Scheduler(new Items($requester, $item->id)))->postAction(Action::Create, array(
            'start' => $start->format('c'),
            'end' => $start->modify('+1 hour')->format('c'),
        ));
        $req = Db::getConnection()->prepare('UPDATE team_events SET start = DATE_SUB(NOW(), INTERVAL 1 DAY), end = DATE_SUB(NOW(), INTERVAL 23 HOUR) WHERE id = :id');
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        Db::getConnection()->execute($req);

        (new BookingApproval($approver))->decide($id, 'approved');
        self::assertSame(BookingApprovalStatus::Approved->value, (int) (new Scheduler(new Items($requester, $item->id), $id))->readOne()['approval_status']);
    }

    public function testMyRequestsShowsRejectedBookingsAndTheRejectionReason(): void
    {
        [$item, $approver, $requester] = $this->getApprovalFixture();
        $start = new DateTimeImmutable('+10 days 10:00');
        $end = $start->modify('+1 hour');
        $id = (new Scheduler(new Items($requester, $item->id)))->postAction(Action::Create, array(
            'start' => $start->format('c'), 'end' => $end->format('c'),
        ));

        (new BookingApproval($approver))->decide($id, 'rejected', 'Maintenance');
        $scheduler = new Scheduler(new Items($requester, $item->id), null, $start->format('c'), $end->format('c'));
        $query = $scheduler->getQueryParams(new InputBag(array(
            'approval_status' => '1,2,3', 'userid' => $requester->userid, 'items' => array($item->id),
        )));
        $myRequests = $scheduler->readAll($query);
        self::assertCount(1, $myRequests);
        self::assertSame($id, (int) $myRequests[0]['id']);
        self::assertSame(BookingApprovalStatus::Rejected->value, (int) $myRequests[0]['approval_status']);
        self::assertSame('Maintenance', $myRequests[0]['approval_comment']);
    }

    public function testPendingListContainsOnlyBookingsAssignedToTheApprover(): void
    {
        [$item, $approver, $requester] = $this->getApprovalFixture();
        $start = new DateTimeImmutable('+11 days 10:00');
        $end = $start->modify('+1 hour');
        $id = (new Scheduler(new Items($requester, $item->id)))->postAction(Action::Create, array(
            'start' => $start->format('c'), 'end' => $end->format('c'),
        ));

        $scheduler = new Scheduler(new Items($approver, $item->id), null, $start->format('c'), $end->format('c'));
        $query = $scheduler->getQueryParams(new InputBag(array(
            'approval_status' => '1', 'approver' => $approver->userid, 'items' => array($item->id),
        )));
        $requests = $scheduler->readAll($query);
        self::assertCount(1, $requests);
        self::assertSame($id, (int) $requests[0]['id']);
        self::assertSame(1, (int) $requests[0]['canapprove']);
        // Filtering by approver is not tied to the authenticated user.
        $otherScheduler = new Scheduler(new Items($requester, $item->id), null, $start->format('c'), $end->format('c'));
        $otherQuery = $otherScheduler->getQueryParams(new InputBag(array(
            'approval_status' => '1', 'approver' => $approver->userid, 'items' => array($item->id),
        )));
        $otherRequests = $otherScheduler->readAll($otherQuery);
        self::assertCount(1, $otherRequests);
        self::assertSame(0, (int) $otherRequests[0]['canapprove']);
    }

    public function testPendingListExcludesBookingsAssignedToAnotherApprover(): void
    {
        [$item, $approver, $requester] = $this->getApprovalFixture();
        // getUserInTeam() always returns the same user, so pick a distinct approver explicitly
        $otherApprover = $this->getOtherUser(array($approver->userid, $requester->userid));
        $otherItem = $this->getFreshBookableItem(2);
        $otherItem->patch(Action::Update, array('requires_booking_approval' => 1, 'approvers' => array($otherApprover->userid)));
        $start = new DateTimeImmutable('+12 days 10:00');
        $end = $start->modify('+1 hour');
        $params = array('start' => $start->format('c'), 'end' => $end->format('c'));
        $id = (new Scheduler(new Items($requester, $item->id)))->postAction(Action::Create, $params);
        $otherId = (new Scheduler(new Items($requester, $otherItem->id)))->postAction(Action::Create, $params);

        foreach (array(array($approver, $id), array($otherApprover, $otherId)) as [$user, $expectedId]) {
            // the approver may not be able to read the resource: the pending list does not need it loaded
            $scheduler = new Scheduler(new Items($user), null, $start->format('c'), $end->format('c'));
            $requests = $scheduler->readAll($scheduler->getQueryParams(new InputBag(array(
                'approval_status' => (string) BookingApprovalStatus::Pending->value,
                'approver' => $user->userid,
                'items' => array($item->id, $otherItem->id),
            ))));
            self::assertCount(1, $requests);
            self::assertSame($expectedId, (int) $requests[0]['id']);
            self::assertSame(1, (int) $requests[0]['canapprove']);
        }
    }

    public function testInvalidApprovalStatusFilterIsRejected(): void
    {
        $item = $this->getFreshBookableItem(2);
        $scheduler = new Scheduler($item);
        $this->expectException(ImproperActionException::class);
        $scheduler->readAll($scheduler->getQueryParams(new InputBag(array('approval_status' => '1,4'))));
    }

    /** Get a validated, non archived user that is not one of the excluded users */
    private function getOtherUser(array $excludedUserids): Users
    {
        $req = Db::getConnection()->prepare('SELECT users.userid, users2teams.teams_id FROM users
            INNER JOIN users2teams ON (users2teams.users_id = users.userid AND users2teams.is_archived = 0)
            WHERE users.validated = 1 AND users.userid NOT IN (' . implode(', ', array_map('intval', $excludedUserids)) . ')
            ORDER BY users.userid LIMIT 1');
        Db::getConnection()->execute($req);
        $user = $req->fetch();
        self::assertIsArray($user);
        return new Users((int) $user['userid'], (int) $user['teams_id']);
    }

    /** @return array{Items, Users, Users} */
    private function getApprovalFixture(): array
    {
        $item = $this->getFreshBookableItem(2);
        $approver = $this->getUserInTeam(2);
        $requester = $this->getUserInTeam(2, admin: 1);
        $item->patch(Action::Update, array('requires_booking_approval' => 1, 'approvers' => array($approver->userid)));
        return array($item, $approver, $requester);
    }
}
