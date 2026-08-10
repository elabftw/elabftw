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

use Elabftw\Enums\AuthMethod;
use Elabftw\Interfaces\LoginStepInterface;

final readonly class UserLoginContext implements LoginStepInterface
{
    public function __construct(
        private int $userid,
        private int $teamId,
        public AuthMethod $authMethod,
    ) {}

    public function isAnonymous(): bool
    {
        return false;
    }

    public function getUserid(): int
    {
        return $this->userid;
    }

    public function getTeam(): int
    {
        return $this->teamId;
    }

    public function getAuthMethod(): AuthMethod
    {
        return $this->authMethod;
    }
}
