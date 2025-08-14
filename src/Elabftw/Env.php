<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;

use function filter_var;
use function getenv;
use function strtolower;

/**
 * For dealing with Environment values passed to php-fpm
 */
final class Env
{
    public static function asString(string $key): string
    {
        return (string) getenv($key);
    }

    public static function asInt(string $key): int
    {
        return (int) getenv($key);
    }

    public static function asBool(string $key): bool
    {
        $val = getenv($key);
        if ($val === false) {
            // not set will be bool false
            return false;
        }
        return strtolower($val) === 'true';
    }

    public static function asUrl(string $key): string
    {
        $key = self::asString($key);
        if (filter_var($key, FILTER_VALIDATE_URL) === false) {
            throw new ImproperActionException(sprintf('Error fetching %s: malformed URL format.', $key));
        }
        return $key;
    }
}
