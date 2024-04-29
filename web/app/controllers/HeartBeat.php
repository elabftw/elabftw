<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Make sure that the user is still logged in
 */
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$Session = new Session();
$Session->start();

$Response = new Response();
// default is 401
$statusCode = Response::HTTP_UNAUTHORIZED;

if ($Session->get('is_auth')) {
    // update the session with something so it stays alive
    $Session->set('last_seen', time());
    $statusCode = Response::HTTP_OK;
}
$Response->setStatusCode($statusCode);
$Response->send();
