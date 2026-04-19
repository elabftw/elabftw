<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @author Moustapha / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\MissingRequiredKeyException;

use function array_filter;
use function array_key_exists;
use function is_string;
use function sprintf;

final class Guard
{
    public static function getValueOfRequiredParam(string $requiredKey, array $params): mixed
    {
        return self::ensureRequiredKeysPresent(array($requiredKey), $params)[$requiredKey];
    }

    public static function getNonEmptyStringValueOfRequiredParam(string $requiredKey, array $params): string
    {
        $value = self::getValueOfRequiredParam($requiredKey, $params);
        if (is_string($value) && $value !== '') {
            return $value;
        }
        throw new ImproperActionException(sprintf('Empty value found for %s', $requiredKey));
    }

    public static function getNonZeroPositiveIntValueOfRequiredParam(string $requiredKey, array $params): int
    {
        $value = (int) self::getValueOfRequiredParam($requiredKey, $params);
        if ($value > 0) {
            return $value;
        }
        throw new ImproperActionException(sprintf('Wrong value found for %s', $requiredKey));
    }

    public static function ensureRequiredKeysPresent(array $requiredKeys, array $params): array
    {
        $missing = array_filter(
            $requiredKeys,
            static fn(string $key) =>
                !array_key_exists($key, $params) || $params[$key] === null
        );
        if ($missing !== array()) {
            throw new MissingRequiredKeyException(
                $missing,
                $requiredKeys,
            );
        }
        return $params;
    }

    public static function ensurePositiveInts(array $keys, array $params): void
    {
        foreach ($keys as $key) {
            if ((int) $params[$key] <= 0) {
                throw new MissingRequiredKeyException(array($key), $keys);
            }
        }
    }
}
