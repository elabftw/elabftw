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

use Elabftw\Traits\EnumsTrait;

enum FileFromString: string
{
    use EnumsTrait;

    case ChemJson = 'chemjson';
    case Json = 'json';
    case Mol = 'mol';
    case Png = 'png';
    case Rxn = 'rxn';
    case Xlsx = 'xlsx';
}
