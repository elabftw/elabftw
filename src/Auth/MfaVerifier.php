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

use Elabftw\Exceptions\InvalidMfaCodeException;
use Elabftw\Interfaces\MfaVerifierInterface;
use Elabftw\Models\Users\Users;
use Elabftw\Params\UserParams;
use Elabftw\Services\MfaHelper;
use Override;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

use function is_string;

final readonly class MfaVerifier implements MfaVerifierInterface
{
    public function __construct(
        private FlashBagAwareSessionInterface $Session,
    ) {}

    #[Override]
    public function verify(int $userid, string $code): void
    {
        $user = new Users($userid);

        $secret = $user->userData['mfa_secret'] ?? $this->Session->get('mfa_secret');

        if (
            !is_string($secret)
            || $secret === ''
            || !new MfaHelper($secret)->verifyCode($code)
        ) {
            throw new InvalidMfaCodeException();
        }

        // First MFA verification also confirms enrollment.
        if ($user->userData['mfa_secret'] === null) {
            $user->update(new UserParams('mfa_secret', $secret));
        }

        $this->Session->remove('mfa_secret');
    }
}
