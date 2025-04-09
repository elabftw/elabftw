<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Revisions;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Show history of body of experiment or db item
 *
 */
require_once 'app/init.inc.php';
// default response is error page with general error message
/** @psalm-suppress UncaughtThrowInGlobalScope */
$Response = new Response();
$Response->prepare($App->Request);

try {
    $Entity = EntityType::from($App->Request->query->getString('type'))->toInstance($App->Users);
    $Entity->setId($App->Request->query->getInt('item_id'));
    $Entity->canOrExplode('read');

    $Revisions = new Revisions(
        $Entity,
        (int) $App->Config->configArr['max_revisions'],
        (int) $App->Config->configArr['min_delta_revisions'],
        (int) $App->Config->configArr['min_days_revisions'],
    );
    $revisionsArr = $Revisions->readAll();

    $template = 'revisions.html';
    $renderArr = array(
        'Entity' => $Entity,
        'pageTitle' => _('Revisions'),
        'revisionsArr' => $revisionsArr,
    );

    $Response->setContent($App->render($template, $renderArr));
} catch (IllegalActionException $e) {
    // log notice and show message
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error(true));
    $Response->setContent($App->render($template, $renderArr));
} catch (ImproperActionException $e) {
    // show message to user
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (DatabaseErrorException $e) {
    // log error and show message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
    $Response->setContent($App->render($template, $renderArr));
} finally {
    $Response->send();
}
