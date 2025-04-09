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

use Elabftw\Traits\EnumsTrait;

enum RequestableAction: int
{
    use EnumsTrait;

    case Archive = 10;
    case Lock = 20;
    case Review = 30;
    case Sign = 40;
    case Timestamp = 50;

    public function toHuman(): string
    {
        return match ($this) {
            self::Archive => _('archiving'),
            self::Lock => _('locking'),
            self::Review => _('review'),
            self::Sign => _('signing'),
            self::Timestamp => _('timestamping'),
        };
    }
}
