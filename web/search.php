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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Services\AdvancedSearchQuery;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;

/**
 * The search page
 * Here be dragons!
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Search');

$Experiments = new Experiments($App->Users);
$Database = new Items($App->Users);
$Tags = new Tags($Experiments);
$tagsArr = $Tags->readAll();

$itemsTypesArr = (new ItemsTypes($App->Users))->readAll();
$categoryArr = $statusArr = (new Status(new Teams($App->Users, $App->Users->team)))->readAll();
if ($App->Request->query->get('type') !== 'experiments') {
    $categoryArr = $itemsTypesArr;
}

// TEAM GROUPS
$TeamGroups = new TeamGroups($App->Users);
$teamGroupsArr = $TeamGroups->readGroupsWithUsersFromUser();
$visibilityArr = $TeamGroups->getVisibilityList();

$usersArr = $App->Users->readAllFromTeam();

// WHERE do we search?
if ($App->Request->query->get('type') === 'experiments') {
    $Entity = $Experiments;
} else {
    $Entity = $Database;
}

// EXTENDED SEARCH
// default input for extendedArea
$extended = 'author:"' . $Entity->Users->userData['fullname'] . '" ';
$extendedError = '';

if ($App->Request->query->has('extended') && !empty($App->Request->query->get('extended'))) {
    $extended = trim((string) $App->Request->query->get('extended'));

    $advancedQuery = new AdvancedSearchQuery($extended, new VisitorParameters($Entity->type, $visibilityArr, $teamGroupsArr));
    $whereClause = $advancedQuery->getWhereClause();
    if ($whereClause) {
        $Entity->addToExtendedFilter($whereClause['where'], $whereClause['bindValues']);
    }

    $extendedError = $advancedQuery->getException();
}

// RENDER THE FIRST PART OF THE PAGE (search form)
$renderArr = array(
    'Request' => $App->Request,
    'Experiments' => $Experiments,
    'Database' => $Database,
    'categoryArr' => $categoryArr,
    'itemsTypesArr' => $itemsTypesArr,
    'tagsArr' => $tagsArr,
    'statusArr' => $statusArr,
    'usersArr' => $usersArr,
    'visibilityArr' => $visibilityArr,
    'extended' => $extended,
    'extendedError' => $extendedError,
    'teamGroups' => array_column($teamGroupsArr, 'name'),
    'whereClause' => print_r($whereClause ?? '', true), // only for dev
);
echo $App->render('search.html', $renderArr);

/**
 * Here the search begins
 * If there is a search, there will be get parameters, so this is our main switch
 */
if ($App->Request->query->count() > 0 && $extendedError === '') {
    // PREPARE SQL query
    /////////////////////////////////////////////////////////////////
    if ($App->Request->query->has('type')) {
        // Metadata search
        foreach ($App->Request->query->all('metakey') as $i => $metakey) {
            if (!empty($metakey)) {
                $Entity->addMetadataFilter($metakey, $App->Request->query->all('metavalue')[$i]);
            }
        }

        if ($App->Request->query->get('type') !== 'experiments') {
            // FILTER ON DATABASE ITEMS TYPES
            if (Check::id($App->Request->query->getInt('type')) !== false) {
                $Entity->addFilter('categoryt.id', (string) $App->Request->query->get('type'));
            }
        }

        try {
            $Controller = new SearchController($App, $Entity);
            echo $Controller->show(true)->getContent();
        } catch (ImproperActionException $e) {
            echo Tools::displayMessage($e->getMessage(), 'ko', false);
        }
    }
} else {
    // no search
    echo $App->render('todolist-panel.html', array());
    echo $App->render('footer.html', array());
}
