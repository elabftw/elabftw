<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Notifications;

/**
 * For notifications that are only delivered by email
 */
class EmailOnlyNotifications extends AbstractNotifications
{
    protected function getPref(int $userid): array
    {
        // only mailable
        return array(0, 1);
    }
}
