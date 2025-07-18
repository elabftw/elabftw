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

use InvalidArgumentException;

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
            self::Full => 'allow_permission_full',
            self::Organization => 'allow_permission_organization',
            self::Team => 'allow_permission_team',
            self::User => 'allow_permission_user',
            self::UserOnly => 'allow_permission_useronly',
        };
    }

    // used for extended search (cf. PermissionsHelper::getExtendedSearchAssociativeArray())
    public function slug(): string
    {
        return match ($this) {
            self::Full => 'public',
            self::Organization => 'organization',
            self::Team => 'myteam',
            self::User => 'user',
            self::UserOnly => 'useronly',
        };
    }

    public static function fromKey(string $confName): BasePermissions
    {
        return match ($confName) {
            'allow_permission_full' => self::Full,
            'allow_permission_organization' => self::Organization,
            'allow_permission_team' => self::Team,
            'allow_permission_user' => self::User,
            'allow_permission_useronly' => self::UserOnly,
            default => throw new InvalidArgumentException("Invalid permission key: $confName"),
        };
    }

    // get base permissions that are in active state
    public static function getActiveBase(array $config): array
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
