<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

/**
 * The GHS signal word of a hazardous compound
 */
enum GhsSignalWord: string
{
    case Danger = 'Danger';
    case Warning = 'Warning';

    /**
     * Sources can disagree on the classification of a compound: keep the most severe signal word
     */
    public static function mostSevere(?self $current, ?self $new): ?self
    {
        if ($current === self::Danger || $new === self::Danger) {
            return self::Danger;
        }
        return $current ?? $new;
    }
}
