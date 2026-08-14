<?php

/**
 * @author Jonathan Griffiths <jgriffiths@cyclanabio.com>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

use function _;

/**
 * Values are spaced so a reason can be slotted in later without renumbering, and none is 0
 * so that a non numeric deletion_reason casts to 0 and fails tryFrom() instead of matching a case.
 */
enum ContainerDeletionReason: int
{
    case UsedInAuthorisedWork = 10;
    case NoLongerRequired = 20;
    case ConsentWithdrawn = 30;
    case ApprovalPeriodEnded = 40;
    case ShelfLifeExceeded = 50;
    case Unsuitable = 60;
    case Contaminated = 70;
    case StorageOrTransportIncident = 80;
    case RegisteredInError = 90;
    case Other = 100;

    public function toHuman(): string
    {
        return match ($this) {
            self::UsedInAuthorisedWork => _('Used up in authorised work'),
            self::NoLongerRequired => _('No longer required'),
            self::ConsentWithdrawn => _('Consent withdrawn or use prohibited'),
            self::ApprovalPeriodEnded => _('Approval or retention period ended'),
            self::ShelfLifeExceeded => _('Shelf life exceeded'),
            self::Unsuitable => _('Unsuitable for intended use'),
            self::Contaminated => _('Contaminated'),
            self::StorageOrTransportIncident => _('Storage or transport incident'),
            self::RegisteredInError => _('Collected or registered in error'),
            self::Other => _('Other'),
        };
    }

    // a catch-all reason says nothing on its own, so it must be spelled out
    public function requiresComment(): bool
    {
        return $this === self::Other;
    }
}
