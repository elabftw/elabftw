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

use function sprintf;

/**
 * Webhooks configured by a user in their control panel. They only see events for entries
 * owned by that user, see WebhooksQueue::fanout(). Submodel for users.
 */
final class UsersWebhooks extends AbstractWebhooks
{
    public function __construct(
        private readonly int $userid,
        bool $canwrite = false,
        ?int $id = null,
    ) {
        parent::__construct($canwrite, $id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('api/v2/users/%d/webhooks/', $this->userid);
    }

    #[Override]
    protected function getScope(): WebhookScope
    {
        return WebhookScope::User;
    }

    #[Override]
    protected function getTeamId(): ?int
    {
        return null;
    }

    #[Override]
    protected function getUserId(): int
    {
        return $this->userid;
    }
}
