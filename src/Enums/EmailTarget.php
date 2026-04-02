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

enum EmailTarget: string
{
    case Team = 'team';
    case TeamGroup = 'teamgroup';
    case Admins = 'admins';
    case Sysadmins = 'sysadmins';
    case BookableItem = 'bookable_item';
    case BookableItemRange = 'bookable_item_range';
    case ActiveUsers = 'active_users';
    case AdminsOfTeam = 'admins_of_team';

    public function needsId(): bool
    {
        return $this === self::Team
            || $this === self::TeamGroup
            || $this === self::BookableItem
            || $this === self::BookableItemRange
            || $this === self::AdminsOfTeam;
    }
}
