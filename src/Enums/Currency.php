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

enum Currency: int
{
    case CAD = 0;
    case CHF = 1;
    case CNY = 2;
    case DKK = 3;
    case EUR = 4;
    case GBP = 5;
    case JPY = 6;
    case USD = 7;
    case SEK = 8;
    case NOK = 9;

    public function toHuman(): string
    {
        return match ($this) {
            self::CAD => _('Canadian Dollar (CAD)'),
            self::CHF => _('Swiss Franc (CHF)'),
            self::CNY => _('Chinese Yuan (CNY)'),
            self::DKK => _('Danish Krone (DKK)'),
            self::EUR => _('Euro (EUR)'),
            self::GBP => _('British Pound Sterling (GBP)'),
            self::JPY => _('Japanese Yen (JPY)'),
            self::USD => _('United States Dollar (USD)'),
            self::SEK => _('Swedish Krona (SEK)'),
            self::NOK => _('Norwegian Krone (NOK)'),
        };
    }

    public function toSymbol(): string
    {
        return match ($this) {
            self::CAD, self::USD => '$',
            self::CHF => 'CHF',
            self::CNY, self::JPY => '¥',
            self::EUR => '€',
            self::GBP => '£',
            self::DKK, self::SEK, self::NOK => 'kr',
        };
    }

    public static function getAssociativeArray(): array
    {
        $all = array();
        foreach (self::cases() as $case) {
            $all[$case->value] = $case->toHuman();
        }
        return $all;
    }
}
