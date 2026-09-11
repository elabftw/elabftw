<?php

/**
 * @author Moritz IHLER
 * @copyright 2026 Moritz IHLER
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Notifications;

use Elabftw\Enums\Notifications;
use Elabftw\Models\Users\Users;
use Override;

/**
 * A webhook was disabled automatically because it failed too many times in a row.
 * Sent to whoever is responsible for that webhook: the user for a user level webhook,
 * the team admins for a team one, the sysadmins for an instance one.
 */
final class WebhookDisabled extends WebOnlyNotifications
{
    protected Notifications $category = Notifications::WebhookDisabled;

    public function __construct(Users $targetUser, private int $webhookId, private string $url)
    {
        parent::__construct($targetUser);
    }

    #[Override]
    protected function getBody(): array
    {
        return array(
            'webhook_id' => $this->webhookId,
            'url' => $this->url,
        );
    }
}
