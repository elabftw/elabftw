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
 * Webhooks configured by a team admin, they see events from that team. Submodel for teams.
 */
final class TeamsWebhooks extends AbstractWebhooks
{
    public function __construct(
        private readonly int $teamid,
        bool $canwrite = false,
        ?int $id = null,
    ) {
        parent::__construct($canwrite, $id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('api/v2/teams/%d/webhooks/', $this->teamid);
    }

    #[Override]
    protected function getScope(): WebhookScope
    {
        return WebhookScope::Team;
    }

    #[Override]
    protected function getTeamId(): int
    {
        return $this->teamid;
    }

    #[Override]
    protected function getUserId(): ?int
    {
        return null;
    }
}
