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
    case Unit = '•';
    case MicroLiter = 'μL';
    case MilliLiter = 'mL';
    case Liter = 'L';
    case MicroGram = 'μg';
    case MilliGram = 'mg';
    case Gram = 'g';
    case KiloGram = 'kg';
    case Bar = 'bar';
    case Metre = 'm';
    case MillionsOfCells = 'e6 cells';

    public function toHuman(): string
    {
        return match ($this) {
            self::Bar => 'Bar',
            self::Metre => 'Metre',
            default => $this->value,
        };
    }
}
