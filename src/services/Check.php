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
use function in_array;
use function mb_strlen;

/**
 * When values need to be checked
 */
class Check
{
    /** the minimum password length */
    public const MIN_PASSWORD_LENGTH = 8;

    /** cookie is a sha1 sum: 64 chars */
    private const COOKIE_LENGTH = 64;

    /**
     * Check the number of character of a password
     *
     * @param string $password The password to check
     * @throws ImproperActionException
     */
    public static function passwordLength(string $password): bool
    {
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new ImproperActionException(sprintf(_('Password must contain at least %d characters.'), self::MIN_PASSWORD_LENGTH));
        }
        return true;
    }

    /**
     * Check ID is valid (pos int)
     *
     * @param int $id
     * @return int|false
     */
    public static function id(int $id)
    {
        $filter_options = array(
            'options' => array(
                'min_range' => 1,
            ),
        );
        return filter_var($id, FILTER_VALIDATE_INT, $filter_options);
    }

    /**
     * Check id and throw exception if it's wrong
     *
     * @param int $id
     * @return int
     */
    public static function idOrExplode(int $id): int
    {
        if (self::id($id) === false) {
            throw new IllegalActionException('The id parameter is not valid!');
        }
        return $id;
    }

    /**
     * Get only the relevant part of the color: remove the #
     *
     * @param string $color #121212
     * @return string
     */
    public static function color(string $color): string
    {
        $color = filter_var(substr($color, 1, 7), FILTER_SANITIZE_STRING);
        if ($color === false || \mb_strlen($color) !== 6) {
            throw new ImproperActionException('Bad color');
        }
        return $color;
    }

    /**
     * Check the limit value
     *
     * @param int $limit
     * @return int
     */
    public static function limit(int $limit): int
    {
        $filterOptions = array(
            'options' => array(
                'default' => 15,
                'min_range' => 1,
                'max_range' => 500,
            ),
            'flags' => FILTER_NULL_ON_FAILURE,
        );
        return filter_var($limit, FILTER_VALIDATE_INT, $filterOptions);
    }

    /**
     * Check the display size user setting
     *
     * @param string $input
     * @return string
     */
    public static function displaySize(string $input): string
    {
        switch ($input) {
            case 'xs':
                return 'xs';
            case 'md':
                return 'md';
            default:
                return 'lg';
        }
    }

    public static function displayMode(string $input): string
    {
        switch ($input) {
            // Item list (the usual)
            case 'it':
                return 'it';
            // Table format
            case 'tb':
                return 'tb';
            default:
                return 'it';
        }
    }

    /**
     * Check orderby param
     *
     * @param string $input
     * @return string
     */
    public static function orderby(string $input): string
    {
        $allowed = array('cat', 'date', 'title', 'comment', 'lastchange');
        if (!in_array($input, $allowed, true)) {
            throw new ImproperActionException('Invalid orderby');
        }
        return $input;
    }

    /**
     * Check sort param
     *
     * @param string $input
     * @return string
     */
    public static function sort(string $input): string
    {
        $allowed = array('asc', 'desc');
        if (!in_array($input, $allowed, true)) {
            throw new ImproperActionException('Invalid sort');
        }
        return $input;
    }

    /**
     * Check if we have a correct value for visibility
     *
     * @param string $visibility
     * @return string
     */
    public static function visibility(string $visibility): string
    {
        $validArr = array(
            'public',
            'organization',
            'team',
            'user',
        );

        if (!in_array($visibility, $validArr, true) && self::id((int) $visibility) === false) {
            throw new IllegalActionException('The visibility parameter is wrong.');
        }

        return $visibility;
    }

    /**
     * Check if we have a correct value for read/write
     *
     * @param string $rw
     * @return string
     */
    public static function rw(string $rw): string
    {
        $validArr = array(
            'read',
            'write',
        );

        if (!in_array($rw, $validArr, true)) {
            throw new IllegalActionException('The read/write parameter is wrong.');
        }

        return $rw;
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
}
