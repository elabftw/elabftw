<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

enum EmailTarget: string
{
    case Team = 'team';
    case TeamGroup = 'teamgroup';
    case Admins = 'admins';
    case Sysadmins = 'sysadmins';
    case BookableItem = 'bookable_item';
    case ActiveUsers = 'active_users';

    public function needsId(): bool
    {
        return $this === self::Team || $this === self::TeamGroup || $this === self::BookableItem;
    }
}
