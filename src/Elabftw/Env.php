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
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

use function getenv;
use function strtolower;
use function trim;

/**
 * For dealing with Environment values passed to php-fpm
 */
final class Env
{
    public static function asString(string $key): string
    {
        return trim((string) self::get($key));
    }

    public static function asInt(string $key): int
    {
        return (int) self::get($key);
    }

    public static function asBool(string $key): bool
    {
        $val = self::get($key);
        if ($val === false) {
            // not set will be bool false
            return false;
        }
        return strtolower($val) === 'true';
    }

    public static function asUrl(string $key): string
    {
        $val = self::asString($key);
        $validator = Validation::createValidator();
        $violations = $validator->validate($val, new Url());
        if (count($violations) > 0) {
            throw new ImproperActionException(sprintf('Error fetching %s: malformed URL.', $key));
        }
        return $val;
    }

    private static function get(string $key): mixed
    {
        return getenv($key);
    }
}
