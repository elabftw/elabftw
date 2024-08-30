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
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\ProcurementRequests;
use Elabftw\Models\Scheduler;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * The TEAM page
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Team');
// default response is error page with general error message
/** @psalm-suppress UncaughtThrowInGlobalScope */
$Response = new Response();
$Response->prepare($App->Request);

try {
    $Teams = new Teams($App->Users);
    $TeamGroups = new TeamGroups($App->Users);
    $Items = new Items($App->Users);
    $Scheduler = new Scheduler($Items);
    $ItemsTypes = new ItemsTypes($App->Users);
    $bookableItemData = array();

    if ($App->Request->query->has('item') && $App->Request->query->get('item') !== 'all' && !empty($App->Request->query->get('item'))) {
        $Scheduler->Items->setId($App->Request->query->getInt('item'));
        $bookableItemData = $Scheduler->Items->readOne();
    }

    // only the bookable categories
    $bookableItemsArr = $Items->readBookable();
    $categoriesOfBookableItems = array_column($bookableItemsArr, 'category');
    $allItemsTypes = $ItemsTypes->readAll();
    $bookableItemsTypes = array_filter(
        $allItemsTypes,
        fn($a): bool => in_array($a['id'], $categoriesOfBookableItems, true),
    );

    $ProcurementRequests = new ProcurementRequests($Teams);

    $template = 'team.html';
    $renderArr = array(
        'bookableItemData' => $bookableItemData,
        'bookableItemsTypes' => $bookableItemsTypes,
        'itemsArr' => $bookableItemsArr,
        'teamArr' => $Teams->readOne(),
        'teamGroupsArr' => $TeamGroups->readAll(),
        'teamProcurementRequestsArr' => $ProcurementRequests->readAll(),
        'teamsStats' => $Teams->getStats($App->Users->userData['team']),
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
