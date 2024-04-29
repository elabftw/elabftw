<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Controllers\Apiv1Controller;

use Elabftw\Controllers\Apiv2Controller;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\AuthenticatedUser;
use Elabftw\Models\Users;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function dirname;

/**
 * Entrypoint for API requests. Nginx redirects all the /api/vN requests here.
 */
require_once dirname(__DIR__) . '/init.inc.php';

$canWrite = true;
// switch between a web request and an api request for auth
try {
    if ($App->Request->getMethod() === Request::METHOD_OPTIONS) {
        return new JsonResponse();
    }
    // check if the authorization header starts with Basic it means it's a basic auth header and we ignore it.
    if ($App->Request->server->has('HTTP_AUTHORIZATION') && !str_starts_with($App->Request->server->get('HTTP_AUTHORIZATION'), 'Basic')) {
        // verify the key and load user info
        $ApiKeys = new ApiKeys(new Users());
        $key = $ApiKeys->readFromApiKey($App->Request->server->get('HTTP_AUTHORIZATION') ?? '');
        // replace the Users in App
        $App->Users = new AuthenticatedUser($key['userid'], $key['team']);
        $canWrite = (bool) $key['can_write'];
    } else {
        if ($App->Session->get('is_auth') !== 1) {
            throw new UnauthorizedException();
        }
    }

    if (str_contains($App->Request->server->get('QUERY_STRING'), 'api/v2')) {
        $Controller = new Apiv2Controller($App->Users, $App->Request, $canWrite);
    } else {
        $Controller = new Apiv1Controller($App->Users, $App->Request, $canWrite);
    }
    $Controller->getResponse()->send();
} catch (ImproperActionException $e) {
    (new Response($e->getMessage(), 400))->send();
} catch (UnauthorizedException $e) {
    $error = array(
        'code' => 401,
        'message' => 'Unauthorized',
        'description' => $e->getMessage(),
    );
    (new JsonResponse($error, $error['code']))->send();
}
