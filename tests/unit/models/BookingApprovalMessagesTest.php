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

use Elabftw\AuditEvent\BookingApprovalDecision;
use Elabftw\Enums\AuditCategory;
use Elabftw\Models\Notifications\BookingApprovalRequested;
use Elabftw\Models\Notifications\BookingApprovalUpdated;
use Elabftw\Models\Users\Users;
use PHPUnit\Framework\TestCase;

use function array_merge;
use function json_decode;

final class BookingApprovalMessagesTest extends TestCase
{
    private const array DETAILS = array(
        'requester_fullname' => 'Titi',
        'item_id' => 42,
        'item_title' => 'Microscope',
        'start' => '2026-11-01 10:00:00',
        'end' => '2026-11-01 11:00:00',
        'occurrences' => 2,
        'status' => 'pending',
        'comment' => null,
    );

    public function testRequestedNotificationEmailLinksToPendingApprovals(): void
    {
        $mail = (new BookingApprovalRequested(new Users(), self::DETAILS))->getEmail();
        self::assertNotEmpty($mail['subject']);
        self::assertStringContainsString('Titi', $mail['body']);
        self::assertStringContainsString('Microscope', $mail['body']);
        self::assertStringContainsString('scheduler.php?tab=3', $mail['body']);
    }

    public function testUpdatedNotificationEmailLinksToOwnRequestsAndIncludesReason(): void
    {
        $mail = (new BookingApprovalUpdated(new Users(), array_merge(self::DETAILS, array(
            'status' => 'rejected',
            'comment' => 'Unavailable',
        ))))->getEmail();
        self::assertNotEmpty($mail['subject']);
        self::assertStringContainsString('scheduler.php?tab=2', $mail['body']);
        self::assertStringContainsString('Unavailable', $mail['body']);
    }

    public function testDecisionAuditContainsActorAndRecurrence(): void
    {
        $audit = new BookingApprovalDecision(7, 8, 42, 'rejected', 'test-series');
        $data = json_decode($audit->getJsonBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(AuditCategory::BookingApprovalDecision, $audit->getCategory());
        self::assertSame('Booking 42: rejected', $audit->getBody());
        self::assertSame(7, $data['requester_userid']);
        self::assertSame(8, $data['target_userid']);
        self::assertSame(42, $data['event_id']);
        self::assertSame('test-series', $data['recurrence_id']);
        self::assertSame('rejected', $data['decision']);
    }
}
