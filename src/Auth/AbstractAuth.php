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

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Enums\EnforceMfa;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Users\Users;
use Override;

abstract class AbstractAuth implements AuthInterface
{
    public function __construct(protected readonly EnforceMfa $enforceMfa) {}

    #[Override]
    abstract public function tryAuth(): AuthResponse;

    /**
     * Is MFA enforced for a given user (SysAdmin or Everyone)?
     */
    public static function isMfaEnforced(int $userid, int $enforceMfa): bool
    {
        $EnforceMfaSetting = EnforceMfa::tryFrom($enforceMfa);
        $Users = new Users($userid);

        switch ($EnforceMfaSetting) {
            case EnforceMfa::Everyone:
                return true;
            case EnforceMfa::SysAdmins:
                return $Users->userData['is_sysadmin'] === 1;
            case EnforceMfa::Admins:
                return $Users->isAdminSomewhere();
            default:
                return false;
        }
    }

    protected function isMfaRequired(int $userid): bool
    {
        $Users = new Users($userid);
        if (!empty($Users->userData['mfa_secret'])) {
            return true;
        }

        return match ($this->enforceMfa) {
            EnforceMfa::Everyone => true,
            EnforceMfa::SysAdmins => $Users->userData['is_sysadmin'] === 1,
            EnforceMfa::Admins => $Users->isAdminSomewhere(),
            EnforceMfa::Disabled => false,
        };
    }
}
