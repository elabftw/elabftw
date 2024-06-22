<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum ProcurementState: int
{
    case Pending = 10;
    case Validated = 20;
    case PartiallyReceived = 30;
    case Received = 40;
    case Archived = 50;

    public function toHuman(): string
    {
        return match ($this) {
            self::Pending => _('Pending'),
            self::Validated => _('Validated'),
            self::PartiallyReceived => _('Partially received'),
            self::Received => _('Received'),
            self::Archived => _('Archived'),
        };
    }
}
