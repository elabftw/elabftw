<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

use Elabftw\Traits\EnumsTrait;
use InvalidArgumentException;

enum State: int
{
    use EnumsTrait;

    case Normal = 1;
    case Archived = 2;
    case Deleted = 3;
    case Pending = 4;
    case Processing = 5;
    case Error = 6;

    public static function fromString(string $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }
        throw new InvalidArgumentException("Invalid state: $value");
    }
}
