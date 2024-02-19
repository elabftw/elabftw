<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

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
            self::Experiments => 'experiments.php',
            self::Database => 'database.php',
            self::Scheduler => 'team.php',
        };
    }
}
