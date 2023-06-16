<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

enum BasePermissions: int
{
    case Full = 50;
    case Organization = 40;
    case MyTeams = 30;
    case User = 20;
    case UserOnly = 10;

    public function toHuman(): string
    {
        return match ($this) {
            $this::Full => _('Public'),
            $this::Organization => _('Organization'),
            $this::MyTeams => _('All the teams I am part of'),
            $this::User  => _('Only owner and admins'),
            $this::UserOnly => _('Only owner'),
        };
    }

    public function toJson(): string
    {
        return sprintf('{"base": %d, "teams": [], "teamgroups": [], "users": []}', $this->value);
    }
}
