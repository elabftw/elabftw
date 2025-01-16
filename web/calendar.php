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
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handle calendar feeds
 */
require_once 'app/init.inc.php';

/** @psalm-suppress UncaughtThrowInGlobalScope */
$Response = new Response();
$Response->prepare($App->Request);

// calendar.php?token={alpha numeric string 60 characters}
try {
    $Response = (new CalendarController($App->Request))->getResponse();

} catch (ImproperActionException $e) {
    $Response->setStatusCode(Response::HTTP_BAD_REQUEST);
} catch (Exception $e) {
    // log error
    $App->Log->error('', array('Exception' => $e));
    $Response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
} finally {
    $Response->send();
}
