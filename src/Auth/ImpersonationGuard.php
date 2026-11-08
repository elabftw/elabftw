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

use Elabftw\Enums\ApiSubModels;
use Elabftw\Exceptions\ForbiddenException;
use Symfony\Component\HttpFoundation\Request;

use function _;
use function basename;
use function dirname;
use function explode;
use function in_array;
use function trim;

/**
 * Every restriction applied to a request made while impersonating a user lives here.
 * Impersonation is read-only: it lets a Sysadmin or Admin see the app as the user sees it, never change anything in their name.
 * The only state changing request allowed is the one that stops the impersonation.
 * Secrets of the user are not readable either: the impersonator could keep them and use them once the impersonation is over.
 * This class refuses requests early with a clear message, and the database connection is read-only as a last line of defense.
 */
final class ImpersonationGuard
{
    private const string STOP_ENTRYPOINT = 'ImpersonationController.php';

    private const string API_ENTRYPOINT = 'ApiController.php';

    private const string CONTROLLERS_DIR = '/app/controllers';

    // controllers act on their input whatever the http method, so only these ones can be reached
    // the api dispatches on the method, and the other one only changes the language of the session
    private const array READABLE_CONTROLLERS = array(
        self::API_ENTRYPOINT,
        'UnauthRequestHandler.php',
    );

    // pages that must write to the database while impersonating: stopping it, and logging out, record it in the audit logs
    private const array WRITING_ENTRYPOINTS = array(
        self::STOP_ENTRYPOINT,
        'logout.php',
    );

    public function assertAllowed(Request $Request): void
    {
        $entrypoint = basename($Request->getScriptName());
        // only trust the method of the request line: X-HTTP-Method-Override would let a POST look like a GET
        $method = $Request->getRealMethod();
        if ($Request->getMethod() !== $method) {
            $this->refuse();
        }
        if ($method === Request::METHOD_POST && $entrypoint === self::STOP_ENTRYPOINT) {
            return;
        }
        // other methods can carry a body that some controllers act upon (OPTIONS, TRACE)
        if (!in_array($method, array(Request::METHOD_GET, Request::METHOD_HEAD), true)) {
            $this->refuse();
        }
        // a GET can carry a body too, and controllers read it
        if (dirname($Request->getScriptName()) === self::CONTROLLERS_DIR && !in_array($entrypoint, self::READABLE_CONTROLLERS, true)) {
            $this->refuse();
        }
        // these GET requests change things: the team selection would login as the impersonated user,
        // and opening an entry in edit mode locks it for other users. Read mode like the entity controllers do (getAlpha).
        if (
            ($entrypoint === 'login.php' && $Request->query->get('switch_team') === '1')
            || $Request->query->getAlpha('mode') === 'edit'
        ) {
            $this->refuse();
        }
        if ($entrypoint === self::API_ENTRYPOINT) {
            $this->assertNoSecretIsRead($Request);
        }
    }

    /**
     * Whether this request can write to the database while impersonating
     */
    public function canWrite(Request $Request): bool
    {
        return in_array(basename($Request->getScriptName()), self::WRITING_ENTRYPOINTS, true);
    }

    /**
     * Refuse the api reads that return a secret. The sig_privkey of /users/me is dropped in Users::readOne()
     * and the access keys of entities in EntitySqlBuilder instead, because these endpoints are needed everywhere in the app.
     */
    private function assertNoSecretIsRead(Request $Request): void
    {
        $segments = explode('/', trim($Request->getPathInfo(), '/'));
        foreach ($segments as $index => $segment) {
            // a single webhook (user, team or instance) comes with its decrypted signing secret, the list does not
            if ($segment === ApiSubModels::Webhooks->value && ($segments[$index + 1] ?? '') !== '') {
                $this->refuse();
            }
            // signature keys always come with the private key
            if ($segment === ApiSubModels::SigKeys->value) {
                $this->refuse();
            }
        }
    }

    private function refuse(): never
    {
        throw new ForbiddenException(_('Impersonation is read-only: this action is not possible while impersonating a user.'));
    }
}
