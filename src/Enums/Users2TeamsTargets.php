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

enum Users2TeamsTargets: string
{
    case IsAdmin = 'is_admin';
    case IsArchived = 'is_archived';
    case IsOwner = 'is_owner';

    public function toHuman(): string
    {
        return match ($this) {
            self::IsAdmin => _('Admin'),
            self::IsArchived => _('Archived'),
            self::IsOwner => _('Owner'),
        };
    }
}
