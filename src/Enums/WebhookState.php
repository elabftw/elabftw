<?php

/**
 * @author Moritz IHLER
 * @copyright 2026 Moritz IHLER
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

/**
 * State of a row in the webhooks_queue table.
 * Sending exists so two overlapping drains cannot pick up the same row.
 */
enum WebhookState: int
{
    case Queued = 0;
    case Sending = 1;
    case Delivered = 2;
    case Failed = 3;
}
