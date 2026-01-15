<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Exceptions\MissingRequiredKeyException;

use function array_filter;
use function array_key_exists;

final class ApiParamsValidator
{
    public static function ensureRequiredKeysPresent(array $requiredKeys, array $params): void
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
