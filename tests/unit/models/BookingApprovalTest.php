<?php

declare(strict_types=1);

namespace Elabftw\Models;

use DateTimeImmutable;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BookingApprovalStatus;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\BookingApproval;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;

use function array_map;

final class BookingApprovalTest extends TestCase
{
    use TestsUtilsTrait;

    public function testConfiguredValidatorCanApproveButTeamAdminCannotOverride(): void
    {
        $item = $this->getFreshBookableItem(2);
        $validator = $this->getUserInTeam(2);
        $admin = $this->getUserInTeam(2, admin: 1);
        $item->patch(Action::Update, array(
            'target' => 'book_approval',
            'requires_validation' => 1,
            'userids' => array($validator->userid),
        ));

        $policy = new BookingApproval($validator);
        self::assertTrue($policy->canApprove($item->entityData, $validator->userid));
        self::assertFalse($policy->canApprove($item->entityData, $admin->userid));
        self::assertSame(BookingApprovalStatus::Approved, $policy->initialStatus($item->entityData, $validator->userid));
        self::assertSame(BookingApprovalStatus::Pending, $policy->initialStatus($item->entityData, $admin->userid));

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

        $asValidator = new Scheduler(new Items($validator, $item->id), $eventId);
        $asValidator->patch(Action::Update, array('target' => 'approval', 'decision' => 'approved'));
        self::assertSame(BookingApprovalStatus::Approved->value, (int) $asValidator->readOne()['approval_status']);
    }

    public function testPendingRequestBlocksSlotAndDisablingApprovalConfirmsIt(): void
    {
        $item = $this->getFreshBookableItem(2);
        $validator = $this->getUserInTeam(2);
        $requester = $this->getUserInTeam(2, admin: 1);
        $item->patch(Action::Update, array('target' => 'book_approval', 'requires_validation' => 1, 'userids' => array($validator->userid)));
        $start = new DateTimeImmutable('+4 days 10:00');
        $params = array('start' => $start->format('c'), 'end' => $start->modify('+1 hour')->format('c'));
        $scheduler = new Scheduler(new Items($requester, $item->id));
        $eventId = $scheduler->postAction(Action::Create, $params);
        self::assertSame(BookingApprovalStatus::Pending->value, (int) (new Scheduler(new Items($requester, $item->id), $eventId))->readOne()['approval_status']);

        try {
            $scheduler->postAction(Action::Create, $params);
            self::fail('A pending booking should block this slot.');
        } catch (ImproperActionException) {
            // Expected overlap, even when book_can_overlap is configured on the resource.
        }

        $item->patch(Action::Update, array(
            'target' => 'book_approval',
            'requires_validation' => 0,
            'userids' => array($validator->userid),
        ));
        self::assertSame(BookingApprovalStatus::Approved->value, (int) (new Scheduler(new Items($requester, $item->id), $eventId))->readOne()['approval_status']);
    }

    public function testTeamMemberWithWriteAccessCanConfigureBookingApproval(): void
    {
        $owner = $this->getUserInTeam(2, admin: 1);
        $writer = $this->getUserInTeam(2);
        $item = $this->getFreshItemWithGivenUser($owner);
        $item->patch(Action::Update, array('canwrite_base' => BasePermissions::Team->value));

        $writerItem = new Items($writer, $item->id);
        $writerItem->patch(Action::Update, array(
            'target' => 'book_approval',
            'requires_validation' => 1,
            'userids' => array($writer->userid),
        ));

        self::assertSame(array((int) $writer->userid), $writerItem->getBookingValidatorIds());
        self::assertSame(1, (int) (new Items($owner, $item->id))->entityData['requires_validation']);
    }

    public function testBookingSettingsAndValidatorsAreSavedInOnePatch(): void
    {
        $item = $this->getFreshBookableItem(2);
        $validator = $this->getUserInTeam(2);
        $item->patch(Action::Update, array(
            'book_max_slots' => 3,
            'requires_validation' => 1,
            'userids' => array($validator->userid),
        ));

        $updated = new Items($item->Users, $item->id);
        self::assertSame(3, (int) $updated->entityData['book_max_slots']);
        self::assertSame(1, (int) $updated->entityData['requires_validation']);
        self::assertSame(array((int) $validator->userid), $updated->getBookingValidatorIds());
    }

    public function testInvalidBookingSettingsRollBackValidatorsAndResourceChanges(): void
    {
        $item = $this->getFreshBookableItem(2);
        $validator = $this->getUserInTeam(2);
        $originalSlots = (int) $item->entityData['book_max_slots'];

        try {
            $item->patch(Action::Update, array(
                'book_max_slots' => 3,
                'invalid_setting' => 'x',
                'requires_validation' => 1,
                'userids' => array($validator->userid),
            ));
            self::fail('The PATCH should reject an invalid resource parameter.');
        } catch (ImproperActionException) {
            // Both kinds of settings must remain unchanged.
        }

        $updated = new Items($item->Users, $item->id);
        self::assertSame($originalSlots, (int) $updated->entityData['book_max_slots']);
        self::assertSame(0, (int) $updated->entityData['requires_validation']);
        self::assertSame(array(), $updated->getBookingValidatorIds());
    }

    public function testApprovalRequiredAlsoPreventsOverlappingRecurrenceOccurrences(): void
    {
        $item = $this->getFreshBookableItem(2);
        $validator = $this->getUserInTeam(2);
        $item->patch(Action::Update, array(
            'target' => 'book_approval',
            'requires_validation' => 1,
            'userids' => array($validator->userid),
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
            'target' => 'book_approval',
            'requires_validation' => 1,
            'userids' => array($otherUser->userid),
        ));
    }

    public function testResourceOwnerCanConfigureValidators(): void
    {
        $owner = $this->getUserInTeam(2);
        $item = $this->getFreshItemWithGivenUser($owner);
        $approval = new BookingApproval($owner);
        $approval->configure($item, array('requires_validation' => 1, 'userids' => array($owner->userid)));

        self::assertSame(array((int) $owner->userid), $approval->getDesignatedUserIds((int) $item->id));
        self::assertSame(BookingApprovalStatus::Approved, $approval->initialStatus(
            (new Items($owner, $item->id))->entityData,
            (int) $owner->userid,
        ));
    }

    public function testAdminOfResourceTeamCanConfigureValidators(): void
    {
        $owner = $this->getUserInTeam(2);
        $admin = $this->getUserInTeam(2, admin: 1);
        $item = $this->getFreshItemWithGivenUser($owner);
        (new BookingApproval($admin))->configure($item, array(
            'requires_validation' => 1,
            'userids' => array($owner->userid),
        ));

        self::assertSame(array((int) $owner->userid), (new BookingApproval($admin))->getDesignatedUserIds((int) $item->id));
    }

    public function testApprovalConfigurationRejectsInvalidFlag(): void
    {
        $owner = $this->getUserInTeam(2);
        $item = $this->getFreshItemWithGivenUser($owner);
        $this->expectException(ImproperActionException::class);
        (new BookingApproval($owner))->configure($item, array('requires_validation' => 3, 'userids' => array($owner->userid)));
    }

    public function testApprovalConfigurationRequiresValidatorArray(): void
    {
        $owner = $this->getUserInTeam(2);
        $item = $this->getFreshItemWithGivenUser($owner);
        $this->expectException(ImproperActionException::class);
        (new BookingApproval($owner))->configure($item, array('requires_validation' => 1, 'userids' => 'invalid'));
    }

    public function testApprovalConfigurationRequiresAnActiveValidator(): void
    {
        $owner = $this->getUserInTeam(2);
        $item = $this->getFreshItemWithGivenUser($owner);
        $this->expectException(ImproperActionException::class);
        (new BookingApproval($owner))->configure($item, array('requires_validation' => 1, 'userids' => array(PHP_INT_MAX)));
    }

    public function testApprovalCannotBeEnabledWithoutValidators(): void
    {
        $owner = $this->getUserInTeam(2);
        $item = $this->getFreshItemWithGivenUser($owner);
        $this->expectException(ImproperActionException::class);
        (new BookingApproval($owner))->configure($item, array('requires_validation' => 1, 'userids' => array()));
    }

    public function testConfiguringValidatorsRemovesDuplicatesAndCanBeDisabled(): void
    {
        $owner = $this->getUserInTeam(2);
        $item = $this->getFreshItemWithGivenUser($owner);
        $approval = new BookingApproval($owner);
        $approval->configure($item, array(
            'requires_validation' => 1,
            'userids' => array($owner->userid, $owner->userid),
        ));
        self::assertSame(array((int) $owner->userid), $approval->getDesignatedUserIds((int) $item->id));

        $approval->configure($item, array('requires_validation' => 0, 'userids' => array()));
        $updated = new Items($owner, $item->id);
        self::assertSame(array(), $approval->getDesignatedUserIds((int) $item->id));
        self::assertSame(BookingApprovalStatus::Approved, $approval->initialStatus($updated->entityData, (int) $owner->userid));
        self::assertFalse($approval->canApprove($updated->entityData, (int) $owner->userid));
    }

    public function testRejectedBookingRetainsReasonAndFreesSlot(): void
    {
        $item = $this->getFreshBookableItem(2);
        $validator = $this->getUserInTeam(2);
        $requester = $this->getUserInTeam(2, admin: 1);
        $item->patch(Action::Update, array('target' => 'book_approval', 'requires_validation' => 1, 'userids' => array($validator->userid)));
        $start = new DateTimeImmutable('+6 days 10:00');
        $params = array('start' => $start->format('c'), 'end' => $start->modify('+1 hour')->format('c'));
        $scheduler = new Scheduler(new Items($requester, $item->id));
        $id = $scheduler->postAction(Action::Create, $params);

        (new BookingApproval($validator))->decide($id, 'rejected', 'Maintenance');
        $event = (new Scheduler(new Items($requester, $item->id), $id))->readOne();
        self::assertSame(BookingApprovalStatus::Rejected->value, (int) $event['approval_status']);
        self::assertSame('Maintenance', $event['approval_comment']);
        self::assertSame((int) $validator->userid, (int) $event['approval_decided_by']);

        // Rejected requests no longer occupy a slot.
        $replacement = $scheduler->postAction(Action::Create, $params);
        self::assertNotSame($id, $replacement);
    }

    public function testRecurringBookingDecisionAppliesToAllOccurrences(): void
    {
        $item = $this->getFreshBookableItem(2);
        $validator = $this->getUserInTeam(2);
        $requester = $this->getUserInTeam(2, admin: 1);
        $item->patch(Action::Update, array('target' => 'book_approval', 'requires_validation' => 1, 'userids' => array($validator->userid)));
        $start = new DateTimeImmutable('+7 days 10:00');
        $id = (new Scheduler(new Items($requester, $item->id)))->postAction(Action::Create, array(
            'start' => $start->format('c'),
            'end' => $start->modify('+1 hour')->format('c'),
            'recurrence' => array('frequency' => 'daily', 'interval' => 1, 'count' => 3),
        ));

        (new BookingApproval($validator))->decide($id, 'approved');
        $req = Db::getConnection()->prepare('SELECT approval_status FROM team_events WHERE item = :item AND recurrence_id IS NOT NULL');
        $req->bindValue(':item', $item->id, PDO::PARAM_INT);
        Db::getConnection()->execute($req);
        $statuses = array_map('intval', $req->fetchAll(PDO::FETCH_COLUMN));
        self::assertCount(3, $statuses);
        self::assertSame(array(1, 1, 1), $statuses);
    }

    public function testBookingDecisionRejectsUnknownDecision(): void
    {
        $this->expectException(ImproperActionException::class);
        (new BookingApproval($this->getUserInTeam(2)))->decide(1, 'postponed');
    }

    public function testBookingDecisionRejectsUnknownEvent(): void
    {
        $this->expectException(ImproperActionException::class);
        (new BookingApproval($this->getUserInTeam(2)))->decide(PHP_INT_MAX, 'approved');
    }

    public function testBookingDecisionCannotBeRepeated(): void
    {
        $item = $this->getFreshBookableItem(2);
        $validator = $this->getUserInTeam(2);
        $requester = $this->getUserInTeam(2, admin: 1);
        $item->patch(Action::Update, array('target' => 'book_approval', 'requires_validation' => 1, 'userids' => array($validator->userid)));
        $start = new DateTimeImmutable('+8 days 10:00');
        $id = (new Scheduler(new Items($requester, $item->id)))->postAction(Action::Create, array(
            'start' => $start->format('c'),
            'end' => $start->modify('+1 hour')->format('c'),
        ));

        $approval = new BookingApproval($validator);
        $approval->decide($id, 'rejected');
        $this->expectException(ImproperActionException::class);
        $approval->decide($id, 'rejected');
    }

    public function testPendingBookingCannotBeApprovedAfterItsStart(): void
    {
        $item = $this->getFreshBookableItem(2);
        $validator = $this->getUserInTeam(2);
        $requester = $this->getUserInTeam(2, admin: 1);
        $item->patch(Action::Update, array('target' => 'book_approval', 'requires_validation' => 1, 'userids' => array($validator->userid)));
        $start = new DateTimeImmutable('+9 days 10:00');
        $id = (new Scheduler(new Items($requester, $item->id)))->postAction(Action::Create, array(
            'start' => $start->format('c'),
            'end' => $start->modify('+1 hour')->format('c'),
        ));
        $req = Db::getConnection()->prepare('UPDATE team_events SET start = DATE_SUB(NOW(), INTERVAL 1 DAY), end = DATE_SUB(NOW(), INTERVAL 23 HOUR) WHERE id = :id');
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        Db::getConnection()->execute($req);

        $this->expectException(ImproperActionException::class);
        (new BookingApproval($validator))->decide($id, 'approved');
    }

    public function testMyRequestsShowsRejectedBookingsAndTheRejectionReason(): void
    {
        $item = $this->getFreshBookableItem(2);
        $validator = $this->getUserInTeam(2);
        $requester = $this->getUserInTeam(2, admin: 1);
        $item->patch(Action::Update, array('target' => 'book_approval', 'requires_validation' => 1, 'userids' => array($validator->userid)));
        $start = new DateTimeImmutable('+10 days 10:00');
        $end = $start->modify('+1 hour');
        $id = (new Scheduler(new Items($requester, $item->id)))->postAction(Action::Create, array(
            'start' => $start->format('c'), 'end' => $end->format('c'),
        ));

        (new BookingApproval($validator))->decide($id, 'rejected', 'Maintenance');
        $scheduler = new Scheduler(new Items($requester, $item->id), null, $start->format('c'), $end->format('c'));
        $query = $scheduler->getQueryParams(new InputBag(array(
            'approval_view' => 'my', 'items' => array($item->id),
        )));
        $myRequests = $scheduler->readAll($query);
        self::assertCount(1, $myRequests);
        self::assertSame($id, (int) $myRequests[0]['id']);
        self::assertSame(BookingApprovalStatus::Rejected->value, (int) $myRequests[0]['approval_status']);
        self::assertSame('Maintenance', $myRequests[0]['approval_comment']);
    }

    public function testPendingListContainsOnlyBookingsAssignedToTheValidator(): void
    {
        $item = $this->getFreshBookableItem(2);
        $validator = $this->getUserInTeam(2);
        $requester = $this->getUserInTeam(2, admin: 1);
        $item->patch(Action::Update, array('target' => 'book_approval', 'requires_validation' => 1, 'userids' => array($validator->userid)));
        $start = new DateTimeImmutable('+11 days 10:00');
        $end = $start->modify('+1 hour');
        $id = (new Scheduler(new Items($requester, $item->id)))->postAction(Action::Create, array(
            'start' => $start->format('c'), 'end' => $end->format('c'),
        ));

        $scheduler = new Scheduler(new Items($validator, $item->id), null, $start->format('c'), $end->format('c'));
        $query = $scheduler->getQueryParams(new InputBag(array(
            'approval_view' => 'pending', 'items' => array($item->id),
        )));
        $requests = $scheduler->readAll($query);
        self::assertCount(1, $requests);
        self::assertSame($id, (int) $requests[0]['id']);
        self::assertSame(1, (int) $requests[0]['canapprove']);
    }
}
