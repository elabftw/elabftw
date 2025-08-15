<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum BinaryValue: int
{
    case False = 0;
    case True = 1;

    public function toBoolean(): bool
    {
        return match ($this) {
            self::False => false,
            self::True => true,
        };
    }

    public function inverse(): self
    {
        return match ($this) {
            self::False => self::True,
            self::True => self::False,
        };
    }
}
