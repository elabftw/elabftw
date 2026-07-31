<?php

/**
 * @author Jonathan Griffiths <jgriffiths@cyclanabio.com>
 * @copyright 2026 Jonathan Griffiths
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum ContainerDeletionReason: string
{
    case UsedInAuthorisedWork = 'used_in_authorised_work';
    case NoLongerRequired = 'no_longer_required';
    case ConsentWithdrawn = 'consent_withdrawn';
    case ApprovalPeriodEnded = 'approval_period_ended';
    case ShelfLifeExceeded = 'shelf_life_exceeded';
    case Unsuitable = 'unsuitable';
    case Contaminated = 'contaminated';
    case StorageOrTransportIncident = 'storage_or_transport_incident';
    case RegisteredInError = 'registered_in_error';
    case Other = 'other';

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
}
