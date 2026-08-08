<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Enums\Language;
use Elabftw\Interfaces\LoginContextInterface;
use Override;

final readonly class AnonymousLoginContext implements LoginContextInterface
{
    public function __construct(
        private int $teamId,
        public Language $language,
    ) {}

    #[Override]
    public function isAnonymous(): bool
    {
        return true;
    }

    #[Override]
    public function getUserid(): int
    {
        return 0;
    }

    #[Override]
    public function getTeam(): int
    {
        return $this->teamId;
    }
}
