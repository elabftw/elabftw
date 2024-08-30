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

enum Usergroup: int
{
    case Sysadmin = 1;
    case Admin = 2;
    case User = 4;

    public function toHuman(): string
    {
        return match ($this) {
            $this::Sysadmin => _('Sysadmin'),
            $this::Admin => _('Admin'),
            $this::User => _('User'),
        };
    }
}
