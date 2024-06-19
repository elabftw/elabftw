<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum Entrypoint: int
{
    case Dashboard = 0;
    case Experiments = 1;
    case Database = 2;
    case Scheduler = 3;

    public function toPage(): string
    {
        return match ($this) {
            self::Dashboard => 'dashboard.php',
            self::Experiments => EntityType::Experiments->toPage(),
            self::Database => EntityType::Items->toPage(),
            self::Scheduler => 'team.php',
        };
    }
}
