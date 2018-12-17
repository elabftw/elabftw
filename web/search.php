<?php
/**
 * search.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

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

// TITLE
$title = '';
$titleWithSpace = false;
if ($Request->query->has('title') && !empty($Request->query->get('title'))) {
    $title = \filter_var(\trim($Request->query->get('title')), FILTER_SANITIZE_STRING);
    // check if there is a space in the query
    if (\strrpos($title, " ") !== false) {
        $titleArr = \explode(' ', $title);
        $titleWithSpace = true;
    }
}

// BODY
$body = '';
$bodyWithSpace = false;
if ($Request->query->has('body') && !empty($Request->query->get('body'))) {
    $body = \filter_var(\trim($Request->query->get('body')), FILTER_SANITIZE_STRING);
    // check if there is a space in the query
    if (\strrpos($body, " ") !== false) {
        $bodyArr = \explode(' ', $body);
        $bodyWithSpace = true;
    }
}

// ANDOR
$andor = ' AND ';
if ($Request->query->has('andor') && $Request->query->get('andor') === 'or') {
    $andor = ' OR ';
}

// TAGS
$selectedTagsArr = array();
if ($Request->query->has('tags') && !empty($Request->query->get('tags'))) {
    $selectedTagsArr = $Request->query->get('tags');
}

// VISIBILITY
$vis = '';
if ($Request->query->has('vis') && !empty($Request->query->get('vis')) && $Experiments->checkVisibility($Request->query->get('vis'))) {
    $vis = $Request->query->get('vis');
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
    'tagsArr' => $tagsArr
);
echo $App->render('search.html', $renderArr);

/**
 * Here the search begins
 * If there is a search, there will be get parameters, so this is our main switch
 */
if ($Request->query->count() > 0) {
    // assign variables from get

    $table = 'items';
    $tagTable = 'items_tags';
    $status = '';
    $rating = '';

    // TABLE
    if (isset($_GET['type']) && $_GET['type'] === 'experiments') {
        $table = 'experiments';
        $tagTable = 'experiments_tags';
    }

    // STATUS
    if (isset($_GET['status']) && !empty($_GET['status']) && Tools::checkId((int) $_GET['status']) !== false) {
        $status = $_GET['status'];
    }

    // RATING
    if (isset($_GET['rating']) && !empty($_GET['rating'])) {
        if ($_GET['rating'] === 'no') {
            $rating = 0;
        } else {
            $rating = (int) $_GET['rating'];
        }
    }

    // PREPARE SQL query
    $sqlUserid = '';
    $sqlDate = '';
    $sqlTitle = '';
    $sqlBody = '';
    $sqlTag = '';
    $sqlStatus = '';
    $sqlRating = '';
    $sqlVisibility = '';

    // Title search
    if ($titleWithSpace) {
        $sqlTitle = " AND (";
        foreach ($titleArr as $key => $value) {
            if ($key !== 0) {
                $sqlTitle .= $andor;
            }
            $sqlTitle .= $table . ".title LIKE '%$value%'";
        }
        $sqlTitle .= ")";
    } elseif (!empty($title)) {
        $sqlTitle = " AND " . $table . ".title LIKE '%$title%'";
    }

    // Body search
    if ($bodyWithSpace) {
        $sqlBody = " AND (";
        foreach ($bodyArr as $key => $value) {
            if ($key != 0) {
                $sqlBody .= $andor;
            }
            $sqlBody .= "$table.body LIKE '%$value%'";
        }
        $sqlBody .= ")";
    } elseif (!empty($body)) {
        $sqlBody = " AND $table.body LIKE '%$body%'";
    }

    // Tag search
    if (!empty($selectedTagsArr)) {
        foreach ($selectedTagsArr as $tag) {
            $tag = \filter_var($tag, FILTER_SANITIZE_STRING);
            $sqlTag .= " AND tags.tag LIKE '%" . $tag . "%' ";
        }
    }

    // Status search
    if (!empty($status)) {
        $sqlStatus = " AND $table.status = '$status'";
    }

    // Rating search
    if (!empty($rating)) {
        $sqlRating = " AND $table.rating LIKE '$rating'";
    }

    // Visibility search
    if (!empty($vis)) {
        $sqlVisibility = " AND $table.visibility = '$vis'";
    }

    // Date search
    if (!empty($from) && !empty($to)) {
        $sqlDate = " AND $table.date BETWEEN '$from' AND '$to'";
    } elseif (!empty($from) && empty($to)) {
        $sqlDate = " AND $table.date BETWEEN '$from' AND '99991212'";
    } elseif (empty($from) && !empty($to)) {
        $sqlDate = " AND $table.date BETWEEN '00000101' AND '$to'";
    }

    /////////////////////////////////////////////////////////////////
    if (isset($_GET['type'])) {
        if ($_GET['type'] === 'experiments') {
            // EXPERIMENTS SEARCH
            $Entity = new Experiments($App->Users);

            // USERID FILTER
            if (isset($_GET['owner'])) {
                if (Tools::checkId((int) $_GET['owner']) !== false) {
                    $owner = $_GET['owner'];
                } elseif (empty($_GET['owner'])) {
                    $owner = $App->Users->userid;
                }
                $sqlUserid = " AND experiments.userid = " . $owner;
                if ($_GET['owner'] === '0') {
                    $sqlUserid = '';
                }
            }

            // STATUS
            $Entity->categoryFilter = $sqlStatus;
            // VISIBILITY FILTER
            $Entity->visibilityFilter = $sqlVisibility;

        } else {
            // DATABASE SEARCH
            $Entity = new Database($App->Users);

            // RATING
            $Entity->ratingFilter = $sqlRating;
            if (Tools::checkId((int) $_GET['type']) !== false) {
                // filter on database items types
                $Entity->categoryFilter = "AND items_types.id = " . $_GET['type'];
            }
        }

        // common filters for XP and DB
        $Entity->bodyFilter = $sqlBody;
        $Entity->dateFilter = $sqlDate;
        $Entity->tagFilter = $sqlTag;
        $Entity->titleFilter = $sqlTitle;
        $Entity->useridFilter = $sqlUserid;

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
            'searchPage' => true
        ));
    }
}
