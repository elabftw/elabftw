<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Notifications;

use Override;

/**
 * For notifications that only appear in web and email is not sent
 */
class WebOnlyNotifications extends AbstractNotifications
{
    #[Override]
    protected function getPref(int $userid): array
    {
        // not mailable
        return array(1, 0);
    }
}
