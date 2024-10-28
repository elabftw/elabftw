<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

use function strtolower;

enum Scope: int
{
    case User = 1;
    case Team = 2;
    case Everything = 3;

    public static function toIcon(self $value): string
    {
        return match ($value) {
            Scope::User => 'user',
            Scope::Team => 'users',
            Scope::Everything => 'globe',
        };
    }

    /**
     * Get a string representation of a case
     * "user", "team", or "everything"
     */
    public function toString(): string
    {
        return match ($this) {
            Scope::User => strtolower(Scope::User->name),
            Scope::Team => strtolower(Scope::Team->name),
            Scope::Everything => strtolower(Scope::Everything->name),
        };
    }
}
