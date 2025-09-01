<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Controllers\SycController;
use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * OpenCloning tool page
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    $Response->prepare($Request);
    if (!Env::asBool('USE_OPENCLONING')) {
        throw new ImproperActionException('OpenCloning is disabled on this instance! As a Sysadmin, set USE_OPENCLONING to true in container environment to enable it.');
    }
    $Response = new SycController($App)->getResponse();
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
