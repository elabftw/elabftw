<?php

/**
 * @author Moritz IHLER
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

/**
 * The three levels a webhook can live at.
 * A webhook only ever sees events it is entitled to see, see WebhooksQueue::fanout().
 */
enum WebhookScope: int
{
    case Instance = 0;
    case Team = 1;
    case User = 2;
}
