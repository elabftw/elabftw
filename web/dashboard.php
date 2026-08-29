<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Controllers\DashboardController;
use Exception;
use Symfony\Component\HttpFoundation\Response;

require_once 'app/init.inc.php';

$Response = new Response();

try {
    $Response = new DashboardController($App)->getResponse();
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
