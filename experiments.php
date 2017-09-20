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

/**
 * Entry point for all experiment stuff
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = ngettext('Experiment', 'Experiments', 2);

try {
    $Entity = new Experiments($Users);
    $EntityView = new ExperimentsView($Entity);
    $Status = new Status($Entity->Users);

    // VIEW
    if ($Request->query->get('mode') === 'view') {

        $Entity->setId($Request->query->get('id'));
        $Entity->canOrExplode('read');

        // UPLOADS
        $UploadsView = new UploadsView($Entity->Uploads);

        $template = 'view.html';

        $renderArr = array(
            'Ev' => $EntityView,
            'Entity' => $Entity,
            'Uv' => $UploadsView,
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

        $Revisions = new Revisions($Entity);
        // Uploads
        $UploadsView = new UploadsView($Entity->Uploads);
        $TeamGroups = new TeamGroups($Entity->Users);

        $template = 'edit.html';

        $renderArr = array(
            'Entity' => $Entity,
            'Uv' => $UploadsView,
            'mode' => 'edit',
            'Revisions' => $Revisions,
            'Categories' => $Status,
            'TeamGroups' => $TeamGroups,
            'cleanTitle' => Tools::getCleanTitle($Entity->entityData['title']),
            'maxUploadSize' => Tools::returnMaxUploadSize()
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

        $Status = new Status($Entity->Users);
        $categoryArr = $Status->readAll();

        $Templates = new Templates($Entity->Users);
        $templatesArr = $Templates->readFromUserid();


        // READ ALL ITEMS

        // related filter
        if (Tools::checkId($Request->query->get('related'))) {
            $searchType = 'related';
            $itemsArr = $Entity->readRelated($Request->query->get('related'));

        } else {

            // filter by user only if we are not making a search
            if (!$Users->userData['show_team'] && ($searchType === '' || $searchType === 'filter')) {
                $Entity->setUseridFilter();
            }

            $itemsArr = $Entity->read();
        }

        $template = 'show.html';

        $renderArr = array(
            'Entity' => $Entity,
            'itemsArr' => $itemsArr,
            'searchType' => $searchType,
            'categoryArr' => $categoryArr,
            'templatesArr' => $templatesArr,
            'tag' => $tag,
            'query' => $query
        );
    }

} catch (InvalidArgumentException $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());

} catch (Exception $e) {
    $debug = false;
    $message = $e->getMessage();
    if ($debug) {
        $message .= ' ' . $e->getFile() . '(' . $e->getLine() . ')';
    }
    $template = 'error.html';
    $renderArr = array('error' => $message);
}

echo $App->render($template, $renderArr);
