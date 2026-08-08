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

final readonly class CookieLogin
{
    public function __construct(
        private Cookie $authenticator,
        private UserLoginValidator $loginValidator,
        private int $teamId,
    ) {}

    public function getContext(): UserLoginContext
    {
        $authentication = $this->authenticator->authenticate();

        return $this->loginValidator->validate(
            $authentication,
            $this->teamId,
        );
    }
}
