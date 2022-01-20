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

use function count;
use Elabftw\Controllers\SearchController;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
use Elabftw\Services\AdvancedSearchQuery;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use Symfony\Component\HttpFoundation\Request;

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

$itemsTypesArr = (new ItemsTypes($App->Users))->read(new ContentParams('', 'all'));
$categoryArr = $statusArr = (new Status($App->Users->team))->readAll();
if ($Request->query->get('type') !== 'experiments') {
    $categoryArr = $itemsTypesArr;
}

// TEAM GROUPS
$TeamGroups = new TeamGroups($App->Users);
$teamGroupsArr = $TeamGroups->read(new ContentParams());
$visibilityArr = $TeamGroups->getVisibilityList();

$usersArr = $App->Users->readAllFromTeam();

// WHERE do we search?
if ($Request->query->get('type') === 'experiments') {
    $Entity = $Experiments;
} else {
    $Entity = $Database;
}

// line below is for testing
$whereClauseDevLog = '';

// EXTENDED SEARCH : TODO will need to go into a class
function prepareExtendedSearch(
    Request $Request,
    Experiments | Items $Entity,
    string $type,
    array $visibilityArr,
    string $column = ''
): array {
    $userInput = null;
    if ($Request->query->has($type) && !empty($Request->query->get($type))) {
        $userInput = trim((string) $Request->query->get($type));

        $advancedQuery = new AdvancedSearchQuery($userInput, new VisitorParameters($Entity->type, $visibilityArr, $column));
        $whereClause = $advancedQuery->getWhereClause();
        if ($whereClause) {
            $Entity->addToExtendedFilter($whereClause['where'], $whereClause['bindValues']);
        }

        $searchFeedback = $advancedQuery->getException();
    }
    return array(
        $userInput ?? 'author:"' . $Entity->Users->userData['fullname'] . '" ',
        $searchFeedback ?? '',
        // line below is for testing
        print_r($whereClause ?? '', true),
    );
}

[$extended, $extendedError, $tmp] = prepareExtendedSearch($Request, $Entity, 'extended', $visibilityArr);
$whereClauseDevLog .= $tmp;

// RENDER THE FIRST PART OF THE PAGE (search form)
$renderArr = array(
    'Request' => $Request,
    'Experiments' => $Experiments,
    'Database' => $Database,
    'categoryArr' => $categoryArr,
    'itemsTypesArr' => $itemsTypesArr,
    'tagsArr' => $tagsArr,
    'teamGroupsArr' => $teamGroupsArr,
    'statusArr' => $statusArr,
    'usersArr' => $usersArr,
    'visibilityArr' => $visibilityArr,
    'extended' => $extended,
    'extendedError' => $extendedError,
    // line below is for testing
    'whereClause' => $whereClauseDevLog,
);
echo $App->render('search.html', $renderArr);

/**
 * Here the search begins
 * If there is a search, there will be get parameters, so this is our main switch
 */
if ($Request->query->count() > 0 && $extendedError === '') {

    // STATUS
    $status = '';
    if (Check::id((int) $Request->query->get('status')) !== false) {
        $status = $Request->query->get('status');
    }

    // RATING
    $rating = null;
    $allowedRatings = array('null', '1', '2', '3', '4', '5');
    if (in_array($Request->query->get('rating'), $allowedRatings, true)) {
        $rating = $Request->query->get('rating');
    }

    // PREPARE SQL query

    /////////////////////////////////////////////////////////////////
    if ($Request->query->has('type')) {
        // Tag search
        if (!empty($Request->query->all('tags'))) {
            // get all the ids with that tag
            $ids = $Entity->Tags->getIdFromTags($Request->query->all('tags'), (int) $App->Users->userData['team']);
            if (count($ids) > 0) {
                $Entity->idFilter = Tools::getIdFilterSql($ids);
            }
        }

        // Metadata search
        if ($Request->query->get('metakey')) {
            $Entity->addMetadataFilter($Request->query->get('metakey'), $Request->query->get('metavalue'));
        }

        if ($Request->query->get('type') === 'experiments') {

            // USERID FILTER
            if ($Request->query->has('owner')) {
                $owner = $App->Users->userData['userid'];
                if (Check::id((int) $Request->query->get('owner')) !== false) {
                    $owner = $Request->query->get('owner');
                }
                // all the team is 0 as userid
                if ($Request->query->get('owner') !== '0') {
                    $Entity->addFilter('entity.userid', $owner);
                }
            }

            // Status search
            if (!empty($status)) {
                $Entity->addFilter('entity.category', $status);
            }
        } else {
            // FILTER ON DATABASE ITEMS TYPES
            if (Check::id((int) $Request->query->get('type')) !== false) {
                $Entity->addFilter('categoryt.id', $Request->query->get('type'));
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
