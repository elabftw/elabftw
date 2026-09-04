<?php

/**
 * @author Moritz IHLER
 * @copyright 2026 Moritz IHLER
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\WebhookScope;
use Override;

/**
 * Webhooks configured by a sysadmin, they see events from the whole instance.
 */
final class InstanceWebhooks extends AbstractWebhooks
{
    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/instance/webhooks/';
    }

    #[Override]
    protected function getScope(): WebhookScope
    {
        return WebhookScope::Instance;
    }

    #[Override]
    protected function getTeamId(): ?int
    {
        return null;
    }

    #[Override]
    protected function getUserId(): ?int
    {
        return null;
    }
}
