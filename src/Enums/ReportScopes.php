<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

use Elabftw\Traits\EnumsTrait;

enum ReportScopes: string
{
    use EnumsTrait;

    case Compounds = 'compounds';
    case CompoundsHistory = 'compounds_history';
    case Instance = 'instance';
    case Inventory = 'inventory';
    case StoredCompounds = 'stored_compounds';
    case Team = 'team';
}
