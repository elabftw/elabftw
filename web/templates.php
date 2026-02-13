<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Controllers\ExperimentsController;
use Elabftw\Exceptions\AppException;
use Elabftw\Models\Templates;
use Elabftw\Services\Filter;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Experiments templates main page
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    $Response = new ExperimentsController($App, new Templates($App->Users, Filter::intOrNull($Request->query->getInt('id'))))->getResponse();
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    // autologout if there is elabid in view mode
    // so we don't stay logged in as anon
    if ($App->Request->query->has('elabid')
        && $App->Request->query->get('mode') === 'view'
        && !$App->Request->getSession()->has('is_auth')) {
        $App->Session->invalidate();
    }
    $Response->send();
}
