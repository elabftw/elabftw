<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Enums\EnforceMfa;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\Users;

/**
 * Determine if the user must perform multi-factor authentication
 */
final class MfaGate
{
    public static function isMfaRequired(EnforceMfa $enforceMfa, Users $loggingInUser): bool
    {
        // Anonymous user will never need MFA
        if ($loggingInUser instanceof AnonymousUser) {
            return false;
        }
        // if the user has a value for mfa_secret, then mfa is active for their account
        if (!empty($loggingInUser->userData['mfa_secret'])) {
            return true;
        }

        return match ($enforceMfa) {
            EnforceMfa::Everyone => true,
            EnforceMfa::SysAdmins => $loggingInUser->userData['is_sysadmin'] === 1,
            EnforceMfa::Admins => $loggingInUser->isAdminSomewhere(),
            EnforceMfa::Disabled => false,
        };
    }
}
