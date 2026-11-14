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

namespace Elabftw\Enums;

use function array_map;
use function implode;

enum BookingApprovalStatus: int
{
    case NoApproval = 0;
    case Pending = 1;
    case Approved = 2;
    case Rejected = 3;

    /**
     * Comma separated list of the statuses that occupy a time slot, for use in an SQL IN () clause.
     * Only rejected bookings free their slot.
     */
    public static function slotBlockingSqlList(): string
    {
        return implode(', ', array_map(
            static fn(self $status): int => $status->value,
            array(self::NoApproval, self::Pending, self::Approved),
        ));
    }
}
