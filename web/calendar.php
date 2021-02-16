<?php
/**
 * team.php
 *
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Calendar;
use Elabftw\Models\Experiments;
use Elabftw\Models\Steps;
use Elabftw\Models\Templates;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * The calendar page
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Calendar');
// default response is error page with general error message
$Response = new Response();
$Response->prepare($Request);

try {
    $Calendar = new Calendar($App->Users);
    $Experiment = new Experiments($App->Users);
    $ExperimentStep = new Steps($Experiment);
    $experimentSteps = $ExperimentStep->readPending2Schedule();
    $DisplayParams = new DisplayParams();
    $DisplayParams->adjust($App);
    // make limit very big because we want to see ALL the bookable items here
    $DisplayParams->limit = 900000;

    $Templates = new Templates($App->Users);
    $templatesArr = $Templates->getTemplatesList();
    $templateData = array();
    if ($Request->query->has('templateid')) {
        $Templates->setId((int) $Request->query->get('templateid'));
        $templateData = $Templates->read();
        $permissions = $Templates->getPermissions($templateData);
        if ($permissions['read'] === false) {
            throw new IllegalActionException('User tried to access a template without read permissions');
        }
    }

    $template = 'calendar.html';
    $renderArr = array(
        'Entity' => $Templates,
        'Scheduler' => $Scheduler,
        'teamArr' => $teamArr,
        'templateData' => $templateData,
        'templatesArr' => $templatesArr,
        'calendarLang' => Tools::getCalendarLang($App->Users->userData['lang']),
        'experimentSteps' => $experimentSteps
    );

    $Response->setContent($App->render($template, $renderArr));
} catch (ImproperActionException $e) {
    // show message to user
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (IllegalActionException $e) {
    // log notice and show message
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error(true));
    $Response->setContent($App->render($template, $renderArr));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
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
