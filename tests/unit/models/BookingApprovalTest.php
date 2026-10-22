<?php

declare(strict_types=1);

namespace Elabftw\Models;

use DateTimeImmutable;
use Elabftw\Enums\Action;
use Elabftw\Enums\BookingApprovalStatus;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\BookingApproval;
use Elabftw\Traits\TestsUtilsTrait;
use PHPUnit\Framework\TestCase;

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

    public function testOnlyOwnerOrTeamAdminCanConfigureBookingApproval(): void
    {
        $admin = $this->getUserInTeam(2, admin: 1);
        $otherUser = $this->getUserInTeam(2);
        $item = $this->getFreshItemWithGivenUser($admin);

        $this->expectException(ForbiddenException::class);
        (new BookingApproval($otherUser))->configure($item, array(
            'requires_validation' => 1,
            'userids' => array($otherUser->userid),
        ));
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
}
