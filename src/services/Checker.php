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

use Elabftw\Exceptions\ImproperActionException;

/**
 * When values need to be checked
 */
class Checker
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
    public static function checkPasswordLength(string $password): bool
    {
        if (\mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new ImproperActionException(sprintf(_('Password must contain at least %d characters.'), self::MIN_PASSWORD_LENGTH));
        }
        return true;
    }
}
