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
use PHPUnit\Framework\TestCase;

use function json_decode;

final class BookingApprovalMessagesTest extends TestCase
{
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
