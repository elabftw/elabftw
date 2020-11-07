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
use Elabftw\Models\Database;
use Elabftw\Models\Scheduler;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * The team page
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Team');
// default response is error page with general error message
$Response = new Response();
$Response->prepare($Request);

try {
    $Teams = new Teams($App->Users);
    $teamArr = $Teams->read();
    $teamsStats = $Teams->getStats((int) $App->Users->userData['team']);

    $TeamGroups = new TeamGroups($App->Users);
    $teamGroupsArr = $TeamGroups->read();


    $Database = new Database($App->Users);
    // we only want the bookable type of items
    $Database->addFilter('categoryt.bookable', '1');
    $Scheduler = new Scheduler($Database);

    // disabled because takes too much resources
    //$TagCloud = new TagCloud((int) $App->Users->userData['team']);

    $DisplayParams = new DisplayParams();
    $DisplayParams->adjust($App);
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
            $Scheduler->Database->setId((int) $Request->query->get('item'));
            $selectedItem = $Request->query->get('item');
            $allItems = false;
            // itemData is to display the name/category of the selected item
            $itemData = $Scheduler->Database->read();
            if (empty($itemData)) {
                throw new ImproperActionException(_('Nothing to show with this id'));
            }
        }
    }

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

    $template = 'team.html';
    $renderArr = array(
        'Entity' => $Templates,
        //'TagCloud' => $TagCloud,
        'Scheduler' => $Scheduler,
        'allItems' => $allItems,
        'itemsArr' => $itemsArr,
        'itemData' => $itemData,
        'selectedItem' => $selectedItem,
        'teamArr' => $teamArr,
        'teamGroupsArr' => $teamGroupsArr,
        'teamsStats' => $teamsStats,
        'templateData' => $templateData,
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
