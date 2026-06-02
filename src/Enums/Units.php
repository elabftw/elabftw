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

enum Units: string
{
    case Bar = 'bar';
    case Unit = '•';
    case Metre = 'm';
    case MicroLiter = 'μL';
    case MilliLiter = 'mL';
    case Liter = 'L';
    case MicroGram = 'μg';
    case MilliGram = 'mg';
    case Gram = 'g';
    case KiloGram = 'kg';

    // human readable label for the dropdowns; defaults to the symbol/abbreviation
    public function label(): string
    {
        return match ($this) {
            self::Bar => 'Bar',
            self::Metre => 'Metre',
            default => $this->value,
        };
    }

    /**
     * Built-in units in the order they should appear in the UI dropdowns.
     * The case declaration order above is intentionally NOT the display order, so this is the
     * single source of truth for ordering. Keep it in sync with the cases (covered by a test).
     *
     * @return list<self>
     */
    public static function inDisplayOrder(): array
    {
        return array(
            self::Unit,
            self::MicroLiter,
            self::MilliLiter,
            self::Liter,
            self::MicroGram,
            self::MilliGram,
            self::Gram,
            self::KiloGram,
            self::Bar,
            self::Metre,
        );
    }
}
