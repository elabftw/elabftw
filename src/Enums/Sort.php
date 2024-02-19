<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

enum Sort: string
{
    case Asc = 'asc';
    case Desc = 'desc';

    // get the font awesome css class
    public function toFa(): string
    {
        return match ($this) {
            self::Asc => 'fa-sort-up',
            default => 'fa-sort-down',
        };
    }
}
