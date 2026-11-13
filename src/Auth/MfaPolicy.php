<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Enums\AuthMethod;
use Elabftw\Enums\EnforceMfa;
use Elabftw\Models\Users\Users;

/**
 * Determine if the user must perform multi-factor authentication
 */
final readonly class MfaPolicy
{
    public function __construct(
        private EnforceMfa $enforceMfa,
        // when the identity provider already handles MFA (SAML, external auth), enforcing it here again is redundant
        private bool $enforceOnlyForLocalAuth = false,
    ) {}

    public function isRequired(Users $user, AuthMethod $authMethod): bool
    {
        // a user who configured MFA always gets asked for it, whatever the auth method
        if (!empty($user->userData['mfa_secret'])) {
            return true;
        }

        if ($this->enforceOnlyForLocalAuth && $authMethod !== AuthMethod::Local) {
            return false;
        }

        return match ($this->enforceMfa) {
            EnforceMfa::Everyone => true,
            EnforceMfa::SysAdmins => $user->userData['is_sysadmin'] === 1,
            EnforceMfa::Admins => $user->isAdminSomewhere(),
            EnforceMfa::Disabled => false,
        };
    }
}
