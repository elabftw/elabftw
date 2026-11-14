<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Auth\Impersonation;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Models\Users\Users;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use function dirname;

/**
 * Start or stop impersonating a user. Session based only: this is not reachable with an api key.
 * CSRF token is checked in init.inc.php for POST requests.
 */
require_once dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse('/dashboard.php');

try {
    // a state changing action must never be triggered by a GET (link, image, prefetch...)
    if ($App->Request->getMethod() !== Request::METHOD_POST) {
        throw new ImproperActionException('Invalid request method.');
    }
    if (!$App->Users instanceof AuthenticatedUser) {
        throw new UnauthorizedException();
    }

    $Impersonation = new Impersonation(
        $App->Session,
        $App->Config->configArr[Impersonation::CONFIG_KEY] === '1',
    );

    switch ($App->Request->request->getString('action')) {
        case 'start':
            $Impersonation->start($App->Users, $App->Request->request->getInt('userid'));
            break;
        case 'stop':
            $Impersonation->stop();
            // go back to the users list we came from
            $Impersonator = new Users((int) $App->Session->get('userid'), (int) $App->Session->get('team'));
            $Response = new RedirectResponse($Impersonator->isSysadmin() ? '/sysconfig.php?tab=3' : '/admin.php?tab=3');
            break;
        default:
            throw new ImproperActionException('Invalid action parameter.');
    }
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
