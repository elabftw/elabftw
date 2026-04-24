<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Controllers\InterfaceController;
use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * UI design page
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    $Response->prepare($Request);
    if (!Env::asBool('DEV_MODE')) {
        throw new ImproperActionException('This page is only active with DEV_MODE=1.');
    }
    $Response = new InterfaceController($App)->getResponse();
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
