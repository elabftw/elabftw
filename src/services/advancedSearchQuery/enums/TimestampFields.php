<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Enums;

enum TimestampFields: string
{
    case CreatedAt = 'created_at';
    case LockedAt = 'locked_at';
    case TimestampedAt = 'timestamped_at';
}
