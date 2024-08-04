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

enum Classification: int
{
    case None = 0;
    case Restricted = 10;
    case Confidential = 20;
    case Secret = 30;
    case TopSecret = 40;

    public function toHuman(): string
    {
        return match ($this) {
            self::None => _('None'),
            self::Restricted => _('Restricted'),
            self::Confidential => _('Confidential'),
            self::Secret => _('Secret'),
            self::TopSecret => _('Top Secret'),
        };
    }

    /**
     * Get an array with the value as key and toHuman for text
     */
    public static function getAssociativeArray(): array
    {
        $all = array();
        foreach (self::cases() as $case) {
            $all[$case->value] = $case->toHuman();
        }
        return $all;
    }
}
