<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum Branding: int
{
    case Header = 1;
    case Light = 2;
    case Dark = 3;
    case Favicon = 4;

    public static function toFile(self $case): string
    {
        return match ($case) {
            self::Header => 'logo-header.svg',
            self::Light => 'logo-light.svg',
            self::Dark => 'logo-dark.svg',
            self::Favicon => 'favicon.svg',
        };
    }
}
