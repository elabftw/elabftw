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

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\ProcurementRequests;
use Elabftw\Models\TeamGroups;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * The TEAM page
 */
require_once 'app/init.inc.php';
$Response = new Response();
$Response->prepare($App->Request);

try {
    $TeamGroups = new TeamGroups($App->Users);
    $ProcurementRequests = new ProcurementRequests($App->Teams);

    $template = 'team.html';
    $renderArr = array(
        'pageTitle' => _('Team'),
        'teamGroupsArr' => $TeamGroups->readAll(),
        'teamProcurementRequestsArr' => $ProcurementRequests->readAll(),
        'teamsStats' => $App->Teams->getStats($App->Users->userData['team']),
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
