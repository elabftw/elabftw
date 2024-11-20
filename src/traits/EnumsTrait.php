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

use function array_map;

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

    // create a comma separated list of values
    public static function toCsList(): string
    {
        $values = array_map(fn($case) => $case->value, self::cases());
        return implode(', ', $values);
    }

    // create a comma separated list of values with name
    public static function toCsListVerbose(): string
    {
        return implode(', ', array_map(
            fn($case) => sprintf('%d (%s)', $case->value, $case->name),
            self::cases()
        ));
    }
}
