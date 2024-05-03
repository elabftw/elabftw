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

use Elabftw\Controllers\DatabaseController;
use Elabftw\Controllers\ExperimentsController;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\ExtraFieldsKeys;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\TeamTags;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The search page
 * Here be dragons!
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Advanced search');

// default response is search page without results
$Response = new Response();
$Response->prepare($App->Request);

$Teams = new Teams($App->Users, $App->Users->team);
$TeamTags = new TeamTags($App->Users, $App->Users->userData['team']);

$ExperimentsCategories = new ExperimentsCategories($Teams);
$ExperimentsStatus = new ExperimentsStatus($Teams);
$ItemsTypes = new ItemsTypes($App->Users);
$ItemsStatus = new ItemsStatus($Teams);
$ExtraFieldsKeys = new ExtraFieldsKeys($App->Users, '', -1);

// TEAM GROUPS
$TeamGroups = new TeamGroups($App->Users);
$teamGroupsArr = $TeamGroups->readGroupsWithUsersFromUser();
$PermissionsHelper = new PermissionsHelper();

$usersArr = $App->Users->readAllFromTeam();

// RENDER THE FIRST PART OF THE PAGE (search form)
$renderArr = array(
    'Request' => $App->Request,
    'experimentsCategoriesArr' => $ExperimentsCategories->readAll(),
    'experimentsStatusArr' => $ExperimentsStatus->readAll(),
    'itemsTypesArr' => $ItemsTypes->readAll(),
    'itemsStatusArr' => $ItemsStatus->readAll(),
    'tagsArr' => $TeamTags->readFull(),
    'usersArr' => $usersArr,
    'visibilityArr' => $PermissionsHelper->getAssociativeArray(),
    'teamGroups' => array_column($teamGroupsArr, 'name'),
    'metakeyArrForSelect' => array_column($ExtraFieldsKeys->readAll(), 'extra_fields_key'),
);

$responseContent = $App->render('search.html', $renderArr);

$getFooterContent = fn(): string
    => $App->render('todolist-panel.html', array())
    . $App->render('footer.html', array());

/**
 * Here the search begins
 * If there is a search, there will be get parameters, so this is our main switch
 */
if ($App->Request->query->count() > 0) {
    try {
        // WHERE do we search?
        if ($App->Request->query->get('type') === 'experiments') {
            $Controller = new ExperimentsController($App, new Experiments($App->Users));
        } else {
            $Controller = new DatabaseController($App, new Items($App->Users));
        }

        $controllerResponse = $Controller->show(true);
        if ($controllerResponse instanceof RedirectResponse) {
            $controllerResponse->send();
            exit;
        }
        $responseContent .= $controllerResponse->getContent() ?: '';
    } catch (ImproperActionException $e) {
        $responseContent .= TwigFilters::displayMessage($e->getMessage(), 'ko', false);
        $responseContent .= $getFooterContent();
    }
} else {
    // no search
    $responseContent .= $getFooterContent();
}

$Response->setContent($responseContent);
$Response->send();
