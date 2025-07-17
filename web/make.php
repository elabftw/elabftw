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

use Elabftw\Controllers\MakeController;
use Elabftw\Exceptions\AppException;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Create a csv, zip or pdf file
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    $Response = new MakeController($App->Users, $App->Request)->getResponse();
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
