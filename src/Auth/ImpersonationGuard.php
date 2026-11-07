<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Exceptions\ForbiddenException;
use Symfony\Component\HttpFoundation\Request;

use function _;
use function basename;

/**
 * Every restriction applied to a request made while impersonating a user lives here.
 * Impersonation is read-only: it lets a Sysadmin or Admin see the app as the user sees it, never change anything in their name.
 * The only state changing request allowed is the one that stops the impersonation.
 */
final class ImpersonationGuard
{
    private const string STOP_ENTRYPOINT = 'ImpersonationController.php';

    public function assertAllowed(Request $Request): void
    {
        $entrypoint = basename($Request->getScriptName());
        if ($Request->isMethodSafe()) {
            // these GET requests change things: the team selection would login as the impersonated user,
            // and opening an entry in edit mode locks it for other users
            if (
                ($entrypoint === 'login.php' && $Request->query->get('switch_team') === '1')
                || $Request->query->get('mode') === 'edit'
            ) {
                $this->refuse();
            }
            return;
        }
        if ($entrypoint !== self::STOP_ENTRYPOINT) {
            $this->refuse();
        }
    }

    private function refuse(): never
    {
        throw new ForbiddenException(_('Impersonation is read-only: this action is not possible while impersonating a user.'));
    }
}
