<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Traits;

trait EnumsTrait
{
    /**
     * Get an array with the value as key and i18n of case name
     */
    public static function getAssociativeArray(): array
    {
        $all = array();
        foreach (self::cases() as $case) {
            $all[$case->value] = _($case->name);
        }
        return $all;
    }

    /**
     * Get an array with the values
     *
     * @return (string|int)[]
     */
    public static function toArray(): array
    {
        return array_map(
            /** @phpstan-ignore return.unusedType */
            fn(self $case): string|int => $case->value,
            self::cases(),
        );
    }

    /**
     * Get a comma separated list of values
     */
    public static function toCsList(): string
    {
        return implode(', ', self::toArray());
    }
}
