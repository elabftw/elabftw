<?php

/**
 * @author Moustapha Camara
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

enum EventScope: string
{
    case Event = 'event';
    case Future = 'future';
    case Recurrence = 'recurrence';
}
