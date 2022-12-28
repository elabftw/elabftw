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

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use function filter_var;

use JsonException;
use function mb_strlen;

/**
 * When values need to be checked
 */
class Check
{
    /** the minimum password length */
    public const MIN_PASSWORD_LENGTH = 8;

    /** cookie is a sha256 sum: 64 chars */
    private const COOKIE_LENGTH = 64;

    /** how deep goes the canread/canwrite json */
    private const PERMISSIONS_JSON_MAX_DEPTH = 3;

    /**
     * Check the number of character of a password
     */
    public static function passwordLength(string $password): string
    {
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new ImproperActionException(sprintf(_('Password must contain at least %d characters.'), self::MIN_PASSWORD_LENGTH));
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

    public static function usergroup(int $gid): int
    {
        return match ($gid) {
            1, 2, 4 => $gid,
            default => throw new ImproperActionException('Invalid usergroup value.'),
        };
    }

    /**
     * Get only the relevant part of the color: remove the #
     *
     * @param string $color #121212
     */
    public static function color(string $color): string
    {
        $color = filter_var(substr($color, 1, 7), FILTER_SANITIZE_STRING);
        if ($color === false || mb_strlen($color) !== 6) {
            debug_print_backtrace();
            throw new ImproperActionException('Bad color');
        }
        return $color;
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
                'max_range' => 9999,
            ),
            'flags' => FILTER_NULL_ON_FAILURE,
        );
        return filter_var($limit, FILTER_VALIDATE_INT, $filterOptions);
    }

    /**
     * Check if we have a correct value for visibility
     */
    public static function visibility(string $visibility): string
    {
        try {
            $decoded = json_decode($visibility, true, self::PERMISSIONS_JSON_MAX_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ImproperActionException($visibility . ' The visibility parameter is wrong.');
        }
        // Note: if we want to server-side check for useronly disabled, it would be here, by removing 10
        $allowedBase = array(10, 20, 30, 40, 50);
        if (!in_array($decoded['base'], $allowedBase, true)) {
            throw new ImproperActionException('The base visibility parameter is wrong.');
        }
        $arrayParams = array('teams', 'teamgroups', 'users');
        foreach ($arrayParams as $param) {
            if (!is_array($decoded[$param])) {
                throw new ImproperActionException(sprintf('The visibility parameter %s is wrong.', $param));
            }
        }
        return $visibility;
    }

    /**
     * Check the cookie token
     */
    public static function token(string $token): string
    {
        if (mb_strlen($token) !== self::COOKIE_LENGTH) {
            throw new IllegalActionException('Invalid cookie!');
        }
        return Filter::sanitize($token);
    }

    public static function orcid(string $orcid): string
    {
        if (preg_match('/[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{4}/', $orcid) === 1) {
            return $orcid;
        }
        // note: the input field should prevent any incorrect value from being submitted in the first place
        throw new ImproperActionException('Incorrect value for orcid!');
    }

    public static function accessKey(string $ak): string
    {
        if (preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-1[0-9A-F]{3}-[0-9A-F]{4}-[0-9A-F]{12}$/i', $ak) === 1) {
            return $ak;
        }
        throw new ImproperActionException('Incorrect value for access key!');
    }
}
