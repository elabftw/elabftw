<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

enum FileFromString: string
{
    case Png = 'png';
    case Mol = 'mol';
    case Json = 'json';
    case ChemJson = 'chemjson';
    case Rxn = 'rxn';
}
