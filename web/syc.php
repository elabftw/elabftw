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
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * OpenCloning tool page
 */
require_once 'app/init.inc.php';

// default response is error page with general error message
$Response = new Response();
$Response->prepare($Request);
$template = 'error.html';
if (!$App->Config->boolFromEnv('USE_OPENCLONING')) {
    $renderArr = array('error' => 'OpenCloning is disabled on this instance!');
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
    exit;
}

try {
    $Controller = new SycController($App);
    $Response = $Controller->getResponse();
} catch (IllegalActionException $e) {
    // log notice and show message
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $renderArr = array('error' => Tools::error(true));
    $Response->setContent($App->render($template, $renderArr));
} catch (ImproperActionException $e) {
    // show message to user
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (DatabaseErrorException $e) {
    // log error and show message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $renderArr = array('error' => Tools::error());
    $Response->setContent($App->render($template, $renderArr));
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
