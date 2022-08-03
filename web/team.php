<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Items;
use Elabftw\Models\Scheduler;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * The TEAM page
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Team');
// default response is error page with general error message
$Response = new Response();
$Response->prepare($Request);

try {
    $Teams = new Teams($App->Users);
    $teamArr = $Teams->readOne();
    $teamsStats = $Teams->getStats((int) $App->Users->userData['team']);

    $TeamGroups = new TeamGroups($App->Users);
    $teamGroupsArr = $TeamGroups->readAll();

    $Database = new Items($App->Users);
    // we only want the bookable type of items
    $Database->addFilter('categoryt.bookable', '1');
    $Scheduler = new Scheduler($Database);

    $DisplayParams = new DisplayParams($App->Users, $App->Request);
    // make limit very big because we want to see ALL the bookable items here
    $DisplayParams->limit = 900000;
    $itemsArr = $Database->readShow($DisplayParams);
    $itemData = null;

    $allItems = true;
    $selectedItem = null;
    if ($Request->query->get('item')) {
        if ($Request->query->get('item') === 'all'
            || !$Request->query->has('item')) {
        } else {
            $Scheduler->Items->setId((int) $Request->query->get('item'));
            $selectedItem = $Request->query->get('item');
            $allItems = false;
            // itemData is to display the name/category of the selected item
            $itemData = $Scheduler->Items->read(new ContentParams());
        }
    }

    $Templates = new Templates($App->Users);
    $templatesArr = $Templates->readAll();
    $entityData = array();
    if ($Request->query->has('templateid')) {
        $Templates->setId((int) $Request->query->get('templateid'));
        $entityData = $Templates->readOne();
    }

    $template = 'team.html';
    $renderArr = array(
        'Entity' => $Templates,
        'Scheduler' => $Scheduler,
        'allItems' => $allItems,
        'itemsArr' => $itemsArr,
        'itemData' => $itemData,
        'selectedItem' => $selectedItem,
        'teamArr' => $teamArr,
        'teamGroupsArr' => $teamGroupsArr,
        'teamsStats' => $teamsStats,
        'entityData' => $entityData,
        'templatesArr' => $templatesArr,
        'calendarLang' => Tools::getCalendarLang($App->Users->userData['lang']),
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
