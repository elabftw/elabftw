<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

use Elabftw\Traits\EnumsTrait;

enum CalendarKeys: string
{
    use EnumsTrait;

    case AllEvents = 'all_events';
    case Categories = 'categories';
    case Items = 'items';
    case Title = 'title';
    case Todo = 'todo';
    case UnfinishedStepsScope = 'unfinished_steps_scope';

    public static function toArray(): array
    {
        return array_map(fn(self $case): string => $case->value, self::cases());
    }

    public static function getDefaultValues(): array
    {
        return array(
            self::AllEvents->value => 0,
            self::Categories->value => null,
            self::Items->value => null,
            self::Title->value => _('Untitled'),
            self::Todo->value => 0,
            self::UnfinishedStepsScope->value => 0,
        );
    }
}
