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

$Experiments = new Experiments($Users);
$Database = new Database($Users);

$ItemsTypes = new ItemsTypes($Users);
$categoryArr = $ItemsTypes->readAll();

$Status = new Status($Users);
$statusArr = $Status->readAll();

$TeamGroups = new TeamGroups($Users);
$teamGroupsArr = $TeamGroups->readAll();

$usersArr = $Users->readAllFromTeam();

$title = '';
$titleWithSpace = false;
// TITLE
if (isset($_GET['title']) && !empty($_GET['title'])) {
    // check if there is a space in the query
    if (strrpos(trim($_GET['title']), " ") !== false) {
        $title_arr = explode(' ', trim($_GET['title']));
        $titleWithSpace = true;
    }
    $title = filter_var(trim($_GET['title']), FILTER_SANITIZE_STRING);
}

// BODY
$body = '';
$bodyWithSpace = false;
if (isset($_GET['body']) && !empty($_GET['body'])) {
    if (strrpos(trim($_GET['body']), " ") !== false) {
        $body_arr = explode(' ', trim($_GET['body']));
        $bodyWithSpace = true;
    }
    $body = filter_var(Tools::checkBody(trim($_GET['body'])), FILTER_SANITIZE_STRING);
}

// ANDOR
$andor = ' AND ';
if (isset($_GET['andor']) && ($_GET['andor'] === 'and')) {
    $andSel = " selected='selected'";
} else {
    $andSel = '';
}
if (isset($_GET['andor']) && ($_GET['andor'] === 'or')) {
    $orSel = " selected='selected'";
    $andor = ' OR ';
} else {
    $orSel = '';
}

// TAGS
$tagsArr = array();
if (isset($_GET['type']) && $_GET['type'] === 'experiments' && isset($_GET['tag_exp'])) {
        $tagsArr = $_GET['tag_exp'];
}
if (isset($_GET['type']) && $_GET['type'] === 'database' && isset($_GET['tag_db'])) {
        $tagsArr = $_GET['tag_db'];
}

// VISIBILITY
$vis = '';
if (isset($_GET['vis']) && !empty($_GET['vis'])) {
    if ($Experiments->checkVisibility($_GET['vis'])) {
        $vis = $_GET['vis'];
    }
}

// FROM
if (isset($_GET['from']) && !empty($_GET['from'])) {
    $from = Tools::kdate($_GET['from']);
} else {
    $from = '';
}
// TO
if (isset($_GET['to']) && !empty($_GET['to'])) {
    $to = Tools::kdate($_GET['to']);
} else {
    $to = '';
}

// RENDER THE FIRST PART OF THE PAGE (search form)
$template = 'search.html';
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
    'tagsArr' => $tagsArr
);
echo $App->render($template, $renderArr);

/**
 * Here the search begins
 * If there is a search, there will be get parameters, so this is our main switch
 */
if (isset($_GET)) {
    // assign variables from get

    $table = 'items';
    $tagTable = 'items_tags';
    $status = '';
    $rating = '';
    $tags = '';

    // TABLE
    if (isset($_GET['type']) && $_GET['type'] === 'experiments') {
        $table = 'experiments';
        $tagTable = 'experiments_tags';
    }

    // STATUS
    if (isset($_GET['status']) && !empty($_GET['status']) && Tools::checkId($_GET['status'])) {
        $status = $_GET['status'];
    }

    // RATING
    if (isset($_GET['rating']) && !empty($_GET['rating'])) {
        if ($_GET['rating'] === 'no') {
            $rating = '0';
        } else {
            $rating = intval($_GET['rating']);
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
        foreach ($title_arr as $key => $value) {
            if ($key != 0) {
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
        foreach ($body_arr as $key => $value) {
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
    if (!empty($tagsArr)) {
        foreach ($tagsArr as $tag) {
            $tag = filter_var($tag, FILTER_SANITIZE_STRING);
            $sqlTag .= " AND EXISTS (SELECT 1 FROM " . $tagTable . " tagt WHERE tagt.item_id = " .
                $table . ".id AND tagt.tag LIKE '%" . $tag . "%') ";
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
            $Entity = new Experiments($Users);

            // USERID FILTER
            if (isset($_GET['owner'])) {
                if (Tools::checkId($_GET['owner'])) {
                    $owner = $_GET['owner'];
                    $sqlUserid = " AND experiments.userid = " . $owner;
                } elseif (empty($_GET['owner'])) {
                    $owner = $Users->userid;
                    $sqlUserid = " AND experiments.userid = " . $owner;
                }
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
            $Entity = new Database($Users);

            // RATING
            $Entity->ratingFilter = $sqlRating;
            if (Tools::checkId($_GET['type'])) {
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
