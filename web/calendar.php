<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Controllers\CalendarController;
use Elabftw\Exceptions\ResourceNotFoundException;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Create an account
 */
require_once 'app/init.inc.php';

/** @psalm-suppress UncaughtThrowInGlobalScope */
$Response = new Response();
$Response->prepare($App->Request);

// calendar.php?token={alpha numeric string 60 characters}
try {
    if (!$App->Request->query->has('token') || strlen($App->Request->query->getString('token')) !== 60) {
        throw new ResourceNotFoundException('Missing or invalid calendar token');
    }

    $Response = (new CalendarController($App->Request))->getResponse();

} catch (ResourceNotFoundException $e) {
    // log error and show general error message
    $App->Log->error('', array('Exception' => $e));
    $Response->setStatusCode(Response::HTTP_NOT_FOUND);
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array('Exception' => $e));
    $Response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
} finally {
    $Response->send();
}
