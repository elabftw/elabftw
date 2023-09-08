<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Controllers\SearchController;
use Elabftw\Enums\FilterableColumn;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\TeamTags;
use Elabftw\Services\Check;
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

$Experiments = new Experiments($App->Users);
$Database = new Items($App->Users);
$TeamTags = new TeamTags($App->Users, $App->Users->userData['team']);

$itemsTypesArr = (new ItemsTypes($App->Users))->readAll();
$categoryArr = $statusArr = (new ExperimentsStatus(new Teams($App->Users, $App->Users->team)))->readAll();
if ($App->Request->query->get('type') !== 'experiments') {
    $categoryArr = $itemsTypesArr;
}

// TEAM GROUPS
$TeamGroups = new TeamGroups($App->Users);
$teamGroupsArr = $TeamGroups->readGroupsWithUsersFromUser();
$PermissionsHelper = new PermissionsHelper();

$usersArr = $App->Users->readAllFromTeam();

// WHERE do we search?
if ($App->Request->query->get('type') === 'experiments') {
    $Entity = $Experiments;
} else {
    $Entity = $Database;
}

// RENDER THE FIRST PART OF THE PAGE (search form)
$renderArr = array(
    'Request' => $App->Request,
    'Experiments' => $Experiments,
    'Database' => $Database,
    'categoryArr' => $categoryArr,
    'itemsTypesArr' => $itemsTypesArr,
    'tagsArr' => $TeamTags->readFull(),
    'statusArr' => $statusArr,
    'usersArr' => $usersArr,
    'visibilityArr' => $PermissionsHelper->getAssociativeArray(),
    'teamGroups' => array_column($teamGroupsArr, 'name'),
);

$responseContent = $App->render('search.html', $renderArr);

$getFooterContent = function () use ($App): string {
    return $App->render('todolist-panel.html', array())
        . $App->render('footer.html', array());
};

/**
 * Here the search begins
 * If there is a search, there will be get parameters, so this is our main switch
 */
if ($App->Request->query->count() > 0) {
    // PREPARE SQL query
    /////////////////////////////////////////////////////////////////
    if ($App->Request->query->has('type')) {
        // FILTER ON DATABASE ITEMS TYPES
        if (Check::id($App->Request->query->getInt('type')) !== false) {
            $Entity->addFilter(FilterableColumn::Category->value, $App->Request->query->getInt('type'));
        }

        try {
            $Controller = new SearchController($App, $Entity);
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
    }
} else {
    // no search
    $responseContent .= $getFooterContent();
}

$Response->setContent($responseContent);
$Response->send();
