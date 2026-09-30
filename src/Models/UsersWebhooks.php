<?php

/**
 * @author Moritz IHLER
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\WebhookScope;
use Elabftw\Models\Users\Users;
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

    /**
     * A user webhook is personal: only that user and a sysadmin may manage it.
     *
     * isAdminOf() is too wide here. It holds for an admin of any team the user happens to
     * share, while fanout() matches a user webhook on ownership alone, with no team
     * constraint. An admin of one team could therefore point a webhook at their own endpoint
     * and receive events for the entries that user owns in their other teams, and read the
     * signing secret along the way. A team admin who wants their team's events has team
     * webhooks for that, which are bound to the team in fanout().
     */
    public static function forRequester(Users $requester, int $userid, ?int $id = null): self
    {
        // the property rather than getUserid(), which throws for an anonymous requester: not
        // being a user at all is a refusal like any other, not a server error
        return new self($userid, $requester->userid === $userid || $requester->isSysadmin(), $id);
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
