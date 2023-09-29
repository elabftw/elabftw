<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

use function array_column;
use function array_combine;
use function array_map;

enum EnforceMfa: int
{
    case Disabled = 0;
    case SysAdmins = 1;
    case Admins = 2;
    case Everyone = 3;

    public static function toHuman(self $case): string
    {
        return match ($case) {
            EnforceMfa::Disabled => _('Disabled'),
            EnforceMfa::SysAdmins => _('Sysadmins'),
            EnforceMfa::Admins => _('Admins'),
            EnforceMfa::Everyone => _('Everyone'),
        };
    }

    public static function getAssociativeArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            // we use alternative syntax instead of 'self::toHuman' here because
            // https://github.com/phpstan/phpstan/issues/4376
            array_map(array(__CLASS__, 'toHuman'), self::cases()),
        );
    }
}
