<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\Units;
use Elabftw\Enums\Usergroup;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\Config;
use Elabftw\Models\Users\Users;
use JsonException;

use function ctype_xdigit;
use function filter_var;
use function intval;
use function mb_strlen;
use function mb_substr;
use function array_keys;
use function in_array;
use function sprintf;
use function strlen;

/**
 * When values need to be checked
 */
final class Check
{
    /** how deep goes the canread/canwrite json */
    private const PERMISSIONS_JSON_MAX_DEPTH = 3;

    /**
     * Check the number of character of a password
     */
    public static function passwordLength(string $password): string
    {
        $Config = Config::getConfig();
        $min = (int) $Config->configArr['min_password_length'];
        if (mb_strlen($password) < $min) {
            throw new ImproperActionException(sprintf(_('Password must contain at least %d characters.'), $min));
        }
        return $password;
    }

    /**
     * Check ID is valid (pos int)
     */
    public static function id(int $id): int | false
    {
        $filter_options = array(
            'options' => array(
                'min_range' => 1,
            ),
        );
        return filter_var($id, FILTER_VALIDATE_INT, $filter_options);
    }

    /**
     * Get only the relevant part of the color: remove the #
     *
     * @param string $color #121212
     */
    public static function color(string $color): string
    {
        if (str_starts_with($color, '#')) {
            $color = mb_substr($color, 1, strlen($color));
        }

        if (ctype_xdigit($color) && strlen($color) === 6) {
            return $color;
        }
        throw new ImproperActionException('The color attribute is invalid.');
    }

    /**
     * Check the limit value
     */
    public static function limit(int $limit): int
    {
        $filterOptions = array(
            'options' => array(
                'default' => 15,
                'min_range' => 1,
                'max_range' => 9999999,
            ),
            'flags' => FILTER_NULL_ON_FAILURE,
        );
        return filter_var($limit, FILTER_VALIDATE_INT, $filterOptions);
    }

    public static function unit(string $unit): string
    {
        $value = Units::tryFrom($unit);
        if ($value === null) {
            throw new ImproperActionException('Invalid unit');
        }
        return $value->value;
    }

    /**
     * Check if we have a correct value for visibility
     */
    public static function visibility(string $visibility): string
    {
        try {
            $decoded = json_decode($visibility, true, self::PERMISSIONS_JSON_MAX_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ImproperActionException('The visibility parameter could not be decoded as JSON.');
        }
        // server-side check for allowed base permissions (e.g., 10, 20, 30 etc.)
        $base = BasePermissions::tryFrom($decoded['base']);
        if ($base === null) {
            throw new ImproperActionException('The base visibility parameter is not valid.');
        }

        // Enforce that base is one of the active permissions
        $Config = Config::getConfig();
        // get human readable to display an indicative error
        $activeBaseAssoc = BasePermissions::getActiveBase($Config->configArr);
        $activeBases = array_keys($activeBaseAssoc);
        if (!in_array($base->value, $activeBases, true)) {
            $allowed = implode(', ', $activeBaseAssoc);
            throw new UnprocessableContentException(
                sprintf(
                    'This base permission (%d) is not currently allowed by the system configuration. Allowed values are: %s.',
                    $base->value,
                    $allowed
                )
            );
        }
        $arrayParams = array('teams', 'teamgroups', 'users');
        foreach ($arrayParams as $param) {
            if (!is_array($decoded[$param])) {
                throw new ImproperActionException(sprintf('The visibility parameter %s is wrong.', $param));
            }
        }
        return $visibility;
    }

    public static function accessKey(string $ak): string
    {
        if (preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-1[0-9A-F]{3}-[0-9A-F]{4}-[0-9A-F]{12}$/i', $ak) === 1) {
            return $ak;
        }
        throw new ImproperActionException('Incorrect value for access key!');
    }

    /**
     * Check digit according to ISO/IEC 7064:2003, MOD 11-2
     */
    public static function digit(string $base, int $checksum): bool
    {
        $sum = 0;
        for ($c = 0; $c < strlen($base); $c++) {
            $sum = ($sum + intval($base[$c])) * 2;
        }
        $remainder = $sum % 11;
        return $checksum === ((12 - $remainder) % 11);
    }

    public static function usergroup(Users $requester, Usergroup $group): Usergroup
    {
        if ($group === Usergroup::Sysadmin && $requester->userData['is_sysadmin'] === 0) {
            throw new ImproperActionException('Only a sysadmin can promote another user to sysadmin.');
        }
        // if requester is not Admin (and not Sysadmin either), the only valid usergroup is User
        if (!$requester->isAdmin && $requester->userData['is_sysadmin'] === 0) {
            return Usergroup::User;
        }
        return $group;
    }
}
