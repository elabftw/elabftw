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

/**
 * Entry point for database things
 *
 */
try {
    require_once 'app/init.inc.php';
    $pageTitle = _('Database');
    $selectedMenu = 'Database';
    require_once 'app/head.inc.php';

    $Entity = new Database($Users);

    // VIEW
    if ($Request->query->get('mode') === 'view') {

        // set id
        $Entity->setId($Request->query->get('id'));
        // check permissions
        $Entity->canOrExplode('read');
        $UploadsView = new UploadsView($Entity->Uploads);
        // the mode parameter is for the uploads tpl
        echo $Twig->render('view.html', array(
            'Entity' => $Entity,
            'Uv' => $UploadsView,
            'mode' => 'view'
        ));

    // EDIT
    } elseif ($Request->query->get('mode') === 'edit') {

        // set id
        $Entity->setId($Request->query->get('id'));
        // check permissions
        $Entity->canOrExplode('write');
        // a locked item cannot be edited
        if ($Entity->entityData['locked']) {
            throw new Exception(_('<strong>This item is locked.</strong> You cannot edit it.'));
        }

        $ItemsTypes = new ItemsTypes($Entity->Users);
        $Revisions = new Revisions($Entity);
        $UploadsView = new UploadsView($Entity->Uploads);

        echo $Twig->render('edit.html', array(
            'Entity' => $Entity,
            'Categories' => $ItemsTypes,
            'Revisions' => $Revisions,
            'Uv' => $UploadsView,
            'mode' => 'edit',
            'maxUploadSize' => Tools::returnMaxUploadSize()
        ));

    // DEFAULT MODE IS SHOW
    } else {
        // CATEGORY FILTER
        if (Tools::checkId($Request->query->get('cat'))) {
            $Entity->categoryFilter = "AND items_types.id = " . $Request->query->get('cat');
            $searchType = 'category';
        }
        // TAG FILTER
        if ($Request->query->get('tag') != '') {
            $tag = filter_var($Request->query->get('tag'), FILTER_SANITIZE_STRING);
            $tag = $tag;
            $Entity->tagFilter = "AND tagt.tag LIKE '" . $tag . "'";
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

        // limit the number of items to show if there is no search parameters
        // because with a big database this can be expensive
        if (!$Request->query->has('q') &&
            !$Request->query->has('tag') &&
            !$Request->query->has('filter')) {
            $Entity->setLimit(50);
        }

        $ItemsTypes = new ItemsTypes($Users);
        $categoryArr = $ItemsTypes->readAll();

        $itemsArr = $Entity->read();

        echo $Twig->render('show.html', array(
            'Entity' => $Entity,
            'Request' => $Request,
            'categoryArr' => $categoryArr,
            'itemsArr' => $itemsArr
        ));
    }
} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
