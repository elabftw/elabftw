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

enum RequestableAction: int
{
    use \Elabftw\Traits\EnumsTrait;

    case Archive = 10;
    case Lock = 20;
    case Sign = 40;
    case Timestamp = 50;
}
