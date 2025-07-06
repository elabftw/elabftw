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

enum BasePermissions: int
{
    case Full = 50;
    case Organization = 40;
    case Team = 30;
    case User = 20;
    case UserOnly = 10;

    public function toHuman(): string
    {
        return match ($this) {
            self::Full => _('Everyone including anonymous users'),
            self::Organization => _('Everyone with an account'),
            self::Team => _('Only members of the team'),
            self::User => _('Only owner and admins'),
            self::UserOnly => _('Only owner'),
        };
    }

    public function configKey(): string
    {
        return match ($this) {
            self::Full => 'allow_full',
            self::Organization => 'allow_organization',
            self::Team => 'allow_team',
            self::User => 'allow_user',
            self::UserOnly => 'allow_useronly',
        };
    }

    public static function getBase(array $config): array
    {
        $base = array();
        foreach (self::cases() as $permission) {
            $key = $permission->configKey();
            if (!empty($config[$key]) && $config[$key] === '1') {
                $base[$permission->value] = $permission->toHuman();
            }
        }
        return $base;
    }

    public function toJson(): string
    {
        return sprintf('{"base": %d, "teams": [], "teamgroups": [], "users": []}', $this->value);
    }
}
