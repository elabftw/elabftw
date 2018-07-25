<?php
/**
 * database.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entry point for database things
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Database');
$Response = new Response();
$Response->prepare($Request);

try {

    // show nothing to anon if admin didn't set the DB as public
    if ($App->Session->has('anon') && ($App->teamConfigArr['public_db'] === '0')) {
        throw new Exception(Tools::error(true));
    }

    $Entity = new Database($App->Users);

    // VIEW
    if ($Request->query->get('mode') === 'view') {

        // set id
        $Entity->setId((int) $Request->query->get('id'));
        // check permissions
        $Entity->canOrExplode('read');
        $UploadsView = new UploadsView($Entity->Uploads);
        $Revisions = new Revisions($Entity);
        $revNum = $Revisions->readCount();
        // the mode parameter is for the uploads tpl
        $template = 'view.html';

        // COMMENTS
        $commentsArr = $Entity->Comments->readAll();

        $renderArr = array(
            'Entity' => $Entity,
            'Uv' => $UploadsView,
            'commentsArr' => $commentsArr,
            'mode' => 'view',
            'revNum' => $revNum
        );

    // EDIT
    } elseif ($Request->query->get('mode') === 'edit') {

        // set id
        $Entity->setId((int) $Request->query->get('id'));
        // check permissions
        $Entity->canOrExplode('write');
        // a locked item cannot be edited
        if ($Entity->entityData['locked']) {
            throw new Exception(_('<strong>This item is locked.</strong> You cannot edit it.'));
        }

        $ItemsTypes = new ItemsTypes($Entity->Users);
        $categoryArr = $ItemsTypes->readAll();
        $Revisions = new Revisions($Entity);
        $revNum = $Revisions->readCount();
        $UploadsView = new UploadsView($Entity->Uploads);
        $TeamGroups = new TeamGroups($Entity->Users);
        $visibilityArr = $TeamGroups->getVisibilityList();

        $template = 'edit.html';

        $renderArr = array(
            'Entity' => $Entity,
            'Categories' => $ItemsTypes,
            'Uv' => $UploadsView,
            'categoryArr' => $categoryArr,
            'mode' => 'edit',
            'maxUploadSize' => Tools::getMaxUploadSize(),
            'revNum' => $revNum,
            'visibilityArr' => $visibilityArr
        );

    // DEFAULT MODE IS SHOW
    } else {
        $TeamGroups = new TeamGroups($Entity->Users);
        $visibilityArr = $TeamGroups->getVisibilityList();

        // if this variable is not empty the error message shown will be different if there are no results
        $searchType = null;
        $query = '';

        // CATEGORY FILTER
        if (Tools::checkId((int) $Request->query->get('cat')) !== false) {
            $Entity->categoryFilter = "AND items_types.id = " . $Request->query->get('cat');
            $searchType = 'category';
        }
        // TAG FILTER
        if ($Request->query->get('tag') != '') {
            $tag = filter_var($Request->query->get('tag'), FILTER_SANITIZE_STRING);
            $Entity->tagFilter = "AND tags.tag LIKE '" . $tag . "'";
            $searchType = 'tag';
        }
        // QUERY FILTER
        if ($Request->query->get('q') != '') {
            $query = filter_var($Request->query->get('q'), FILTER_SANITIZE_STRING);
            $Entity->queryFilter = "AND (
                title LIKE '%$query%' OR
                date LIKE '%$query%' OR
                body LIKE '%$query%')";
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
            $Entity->order = 'items_types.ordering';
        } elseif ($order === 'date' || $order === 'rating' || $order === 'title') {
            $Entity->order = 'items.' . $order;
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
        if ($Request->query->has('limit') && Tools::checkId((int) $Request->query->get('limit')) !== false) {
            $limit = $Request->query->get('limit');
        }

        $offset = 0;
        if ($Request->query->has('offset') && Tools::checkId((int) $Request->query->get('offset')) !== false) {
            $offset = $Request->query->get('offset');
        }

        $showAll = true;
        if ($Request->query->get('limit') !== 'over9000') {
            $Entity->setOffset($offset);
            $Entity->setLimit($limit);
            $showAll = false;
        }
        // END PAGINATION

        $ItemsTypes = new ItemsTypes($Entity->Users);
        $categoryArr = $ItemsTypes->readAll();

        $itemsArr = $Entity->read();

        $template = 'show.html';

        $renderArr = array(
            'Entity' => $Entity,
            'Request' => $Request,
            'categoryArr' => $categoryArr,
            'itemsArr' => $itemsArr,
            'offset' => $offset,
            'query' => $query,
            'searchType' => $searchType,
            'showAll' => $showAll,
            'visibilityArr' => $visibilityArr
        );
    }

} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
}

$Response->setContent($App->render($template, $renderArr));
$Response->send();
