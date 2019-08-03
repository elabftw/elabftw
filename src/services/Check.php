<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;

/**
 * When values need to be checked
 */
class Check
{
    /** the minimum password length */
    public const MIN_PASSWORD_LENGTH = 8;

    /**
     * Check the number of character of a password
     *
     * @param string $password The password to check
     * @throws ImproperActionException
     * @return bool
     */
    public static function passwordLength(string $password): bool
    {
        if (\mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
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
            FILTER_NULL_ON_FAILURE,
        );
        return filter_var($limit, FILTER_VALIDATE_INT, $filterOptions) ?? 15;
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

        if (!\in_array($visibility, $validArr, true) && self::id((int) $visibility) === false) {
            throw new IllegalActionException('The visibility parameter is wrong.');
        }

        return $visibility;
    }
}
