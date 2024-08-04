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

/**
 * When signing something, we need to add a meaning to that signature
 */
enum Meaning: int
{
    use EnumsTrait;

    case Approval = 10;
    case Authorship = 20;
    case Responsibility = 30;
    case Review = 40;
    case Safety = 50;
}
