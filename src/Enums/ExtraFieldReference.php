<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

/**
 * Extra field types whose value is the id of another database entry
 */
enum ExtraFieldReference: string
{
    case Compounds = 'compounds';
    case Experiments = 'experiments';
    case Items = 'items';
    case Users = 'users';
}
