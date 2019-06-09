<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;

/**
 * The search page
 * Here be dragons!
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Search');

$Experiments = new Experiments($App->Users);
$Database = new Database($App->Users);
$Tags = new Tags($Experiments);
$tagsArr = $Tags->readAll();

$ItemsTypes = new ItemsTypes($App->Users);
$categoryArr = $ItemsTypes->readAll();

$Status = new Status($App->Users);
$statusArr = $Status->readAll();

$TeamGroups = new TeamGroups($App->Users);
$teamGroupsArr = $TeamGroups->readAll();

$usersArr = $App->Users->readAllFromTeam();

// ANDOR
$andor = ' AND ';
if ($Request->query->has('andor') && $Request->query->get('andor') === 'or') {
    $andor = ' OR ';
}

// WHERE do we search?
if ($Request->query->get('type') === 'experiments') {
    $Entity = $Experiments;
} else {
    $Entity = $Database;
}

// TITLE
if ($Request->query->has('title') && !empty($Request->query->get('title'))) {
    $title = \filter_var(\trim($Request->query->get('title')), FILTER_SANITIZE_STRING);
    $Entity->titleFilter = Tools::getSearchSql($title, $andor, 'title', $Entity->type);
}

// BODY
if ($Request->query->has('body') && !empty($Request->query->get('body'))) {
    $body = \filter_var(\trim($Request->query->get('body')), FILTER_SANITIZE_STRING);
    $Entity->bodyFilter = Tools::getSearchSql($body, $andor, 'body', $Entity->type);
}

// TAGS
$selectedTagsArr = array();
if ($Request->query->has('tags') && !empty($Request->query->get('tags'))) {
    $selectedTagsArr = $Request->query->get('tags');
}

// VISIBILITY
$vis = '';
if ($Request->query->has('vis') && !empty($Request->query->get('vis'))) {
    $vis = Tools::checkVisibility($Request->query->get('vis'));
}

// FROM
$from = '';
if ($Request->query->has('from') && !empty($Request->query->get('from'))) {
    $from = Tools::kdate($Request->query->get('from'));
}

// TO
$to = '';
if ($Request->query->has('to') && !empty($Request->query->get('to'))) {
    $to = Tools::kdate($Request->query->get('to'));
}

// RENDER THE FIRST PART OF THE PAGE (search form)
$renderArr = array(
    'Request' => $Request,
    'Experiments' => $Experiments,
    'Database' => $Database,
    'categoryArr' => $categoryArr,
    'statusArr' => $statusArr,
    'teamGroupsArr' => $teamGroupsArr,
    'usersArr' => $usersArr,
    'title' => $title,
    'body' => $body,
    'andor' => $andor,
    'selectedTagsArr' => $selectedTagsArr,
    'tagsArr' => $tagsArr,
);
echo $App->render('search.html', $renderArr);

/**
 * Here the search begins
 * If there is a search, there will be get parameters, so this is our main switch
 */
if ($Request->query->count() > 0) {

    // STATUS
    $status = '';
    if (Tools::checkId((int) $Request->query->get('status')) !== false) {
        $status = $Request->query->get('status');
    }

    // RATING
    if ($Request->query->get('rating') === 'no') {
        $rating = 0;
    } else {
        $rating = (int) $Request->query->get('rating');
    }

    // PREPARE SQL query

    /////////////////////////////////////////////////////////////////
    if ($Request->query->has('type')) {
        // Tag search
        if (!empty($selectedTagsArr)) {
            $having = 'HAVING ';
            foreach ($selectedTagsArr as $tag) {
                $tag = \filter_var($tag, FILTER_SANITIZE_STRING);
                $having .= "tags LIKE '%$tag%' AND ";
            }
            $Entity->tagFilter .= rtrim($having, ' AND');
        }

        // Visibility search
        if (!empty($vis)) {
            $Entity->visibilityFilter = ' AND ' . $Entity->type . ".visibility = '$vis'";
        }

        // Date search
        if (!empty($from) && !empty($to)) {
            $Entity->dateFilter = ' AND ' . $Entity->type . ".date BETWEEN '$from' AND '$to'";
        } elseif (!empty($from) && empty($to)) {
            $Entity->dateFilter = ' AND ' . $Entity->type . ".date BETWEEN '$from' AND '99991212'";
        } elseif (empty($from) && !empty($to)) {
            $Entity->dateFilter = ' AND ' . $Entity->type . ".date BETWEEN '00000101' AND '$to'";
        }

        if ($Request->query->get('type') === 'experiments') {

            // USERID FILTER
            if ($Request->query->has('owner')) {
                if (Tools::checkId((int) $Request->query->get('owner')) !== false) {
                    $owner = $Request->query->get('owner');
                } elseif (empty($Request->query->get('owner'))) {
                    $owner = $App->Users->userData['userid'];
                }
                $Entity->useridFilter = ' AND experiments.userid = ' . $owner;
                // all the team is 0 as userid
                if ($Request->query->get('owner') === '0') {
                    $Entity->useridFilter = '';
                }
            }

            // Status search
            if (!empty($status)) {
                $Entity->categoryFilter = ' AND ' . $Entity->type . ".category = '$status'";
            }
        } else {
            // Rating search
            if (!empty($rating)) {
                $Entity->ratingFilter = ' AND ' . $Entity->type . ".rating LIKE '$rating'";
            }

            // FILTER ON DATABASE ITEMS TYPES
            if (Tools::checkId((int) $Request->query->get('type')) !== false) {
                $Entity->categoryFilter = 'AND items_types.id = ' . $Request->query->get('type');
            }
        }

        // READ the results
        $itemsArr = $Entity->read();

        // RENDER THE SECOND PART OF THE PAGE
        // with a subpart of show.html (no create new/filter menu, and no head)
        echo $App->render('show.html', array(
            'Entity' => $Entity,
            'itemsArr' => $itemsArr,
            'categoryArr' => $categoryArr,
            // we are on the search page, so we don't want any "click here to create your first..."
            'searchType' => 'something',
            // generate light show page
            'searchPage' => true,
        ));
    }
} else {
    // no search
    echo $App->render('footer.html', array());
}
