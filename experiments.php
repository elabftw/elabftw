<?php
/**
 * experiments.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Exception;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entry point for all experiment stuff
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = ngettext('Experiment', 'Experiments', 2);

try {
    $Entity = new Experiments($App->Users);
    $EntityView = new ExperimentsView($Entity);

    $Status = new Status($App->Users);
    $categoryArr = $Status->readAll();

    // VIEW
    if ($Request->query->get('mode') === 'view') {
        $Entity->setId($Request->query->get('id'));
        $Entity->canOrExplode('read');

        // LINKS
        $linksArr = $Entity->Links->readAll();

        // COMMENTS
        $commentsArr = $Entity->Comments->readAll();

        // UPLOADS
        $UploadsView = new UploadsView($Entity->Uploads);

        // REVISIONS
        $Revisions = new Revisions($Entity);

        $template = 'view.html';

        $renderArr = array(
            'Ev' => $EntityView,
            'Entity' => $Entity,
            'Revisions' => $Revisions,
            'Uv' => $UploadsView,
            'linksArr' => $linksArr,
            'commentsArr' => $commentsArr,
            'cleanTitle' => Tools::getCleanTitle($Entity->entityData['title']),
            'mode' => 'view'
        );

    // EDIT
    } elseif ($Request->query->get('mode') === 'edit') {
        $Entity->setId($Request->query->get('id'));
        // check permissions
        $Entity->canOrExplode('write');
        // a locked experiment cannot be edited
        if ($Entity->entityData['locked']) {
            throw new Exception(_('<strong>This item is locked.</strong> You cannot edit it.'));
        }

        // REVISIONS
        $Revisions = new Revisions($Entity);

        // UPLOADS
        $UploadsView = new UploadsView($Entity->Uploads);

        // TEAM GROUPS
        $TeamGroups = new TeamGroups($Entity->Users);

        // LINKS
        $linksArr = $Entity->Links->readAll();

        // STEPS
        $stepsArr = $Entity->Steps->readAll();

        $template = 'edit.html';

        $renderArr = array(
            'Entity' => $Entity,
            'Revisions' => $Revisions,
            'TeamGroups' => $TeamGroups,
            'Uv' => $UploadsView,
            'categoryArr' => $categoryArr,
            'cleanTitle' => Tools::getCleanTitle($Entity->entityData['title']),
            'lang' => Tools::getCalendarLang($App->Users->userData['lang']),
            'linksArr' => $linksArr,
            'maxUploadSize' => Tools::returnMaxUploadSize(),
            'mode' => 'edit',
            'stepsArr' => $stepsArr
        );

    // DEFAULT MODE IS SHOW
    } else {
        $searchType = '';
        $tag = '';
        $query = '';

        // CATEGORY FILTER
        if (Tools::checkId($Request->query->get('cat'))) {
            $Entity->categoryFilter = " AND status.id = " . $Request->query->get('cat');
            $searchType = 'filter';
        }
        // TAG FILTER
        if ($Request->query->get('tag') != '') {
            $tag = filter_var($Request->query->get('tag'), FILTER_SANITIZE_STRING);
            $Entity->tagFilter = " AND tagt.tag LIKE '" . $tag . "'";
            $searchType = 'tag';
        }
        // QUERY FILTER
        if ($Request->query->get('q') != '') {
            $query = filter_var($Request->query->get('q'), FILTER_SANITIZE_STRING);
            $Entity->queryFilter = " AND (
                title LIKE '%$query%' OR
                date LIKE '%$query%' OR
                body LIKE '%$query%' OR
                elabid LIKE '%$query%'
            )";
            $searchType = 'query';
        }
        // ORDER
        $order = '';

        // load the pref from the user
        if (isset($Entity->Users->userData['orderby'])) {
            $order = $Entity->Users->userData['orderby'];
        }

        // now get pref from the filter-order-sort menu
        if ($Request->query->has('order')) {
            $order = $Request->query->get('order');
        }

        if ($order === 'cat') {
            $Entity->order = 'status.ordering';
        } elseif ($order === 'date' || $order === 'rating' || $order === 'title') {
            $Entity->order = 'experiments.' . $order;
        } elseif ($order === 'comment') {
            $Entity->order = 'experiments_comments.recent_comment';
        }

        // SORT
        $sort = '';

        // load the pref from the user
        if (isset($Entity->Users->userData['sort'])) {
            $sort = $Entity->Users->userData['sort'];
        }

        // now get pref from the filter-order-sort menu
        if ($Request->query->has('sort')) {
            $sort = $Request->query->get('sort');
        }

        if ($sort === 'asc' || $sort === 'desc') {
            $Entity->sort = $sort;
        }

        // PAGINATION
        $limit = $App->Users->userData['limit_nb'];
        if ($Request->query->has('limit') && Tools::checkId($Request->query->get('limit'))) {
            $limit = $Request->query->get('limit');
        }

        $offset = 0;
        if ($Request->query->has('offset') && Tools::checkId($Request->query->get('offset'))) {
            $offset = $Request->query->get('offset');
        }

        $showAll = true;
        if ($Request->query->get('limit') !== 'over9000') {
            $Entity->setOffset($offset);
            $Entity->setLimit($limit);
            $showAll = false;
        }
        // END PAGINATION

        $TeamGroups = new TeamGroups($Entity->Users);
        $visibilityArr = $TeamGroups->getVisibilityList();

        $Templates = new Templates($Entity->Users);
        $templatesArr = $Templates->readFromUserid();

        // READ ALL ITEMS
        if ($App->Session->get('anon')) {
            $Entity->visibilityFilter =  "AND experiments.visibility = 'public'";
            $itemsArr = $Entity->read();

        // related filter
        } elseif (Tools::checkId($Request->query->get('related'))) {
            $searchType = 'related';
            $itemsArr = $Entity->readRelated($Request->query->get('related'));

        } else {
            // filter by user only if we are not making a search
            if (!$Entity->Users->userData['show_team'] && ($searchType === '' || $searchType === 'filter')) {
                $Entity->setUseridFilter();
            }

            $itemsArr = $Entity->read();
        }

        $template = 'show.html';

        $renderArr = array(
            'Entity' => $Entity,
            'categoryArr' => $categoryArr,
            'itemsArr' => $itemsArr,
            'offset' => $offset,
            'query' => $query,
            'searchType' => $searchType,
            'showAll' => $showAll,
            'tag' => $tag,
            'templatesArr' => $templatesArr,
            'visibilityArr' => $visibilityArr
        );
    }
} catch (InvalidArgumentException $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
} catch (Exception $e) {
    $message = $e->getMessage();
    if ($App->Config->configArr['debug']) {
        $message .= ' in ' . $e->getFile() . ' (line ' . $e->getLine() . ')';
    }
    $template = 'error.html';
    $renderArr = array('error' => $message);
} finally {
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
