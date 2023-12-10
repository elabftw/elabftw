<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

enum Scope: int
{
    case User = 1;
    case Team = 2;
    case Everything = 3;

    public static function toIcon(self $value): string
    {
        return match ($value) {
            Scope::User=> 'user',
            Scope::Team => 'users',
            Scope::Everything=> 'globe',
        };
    }
}
