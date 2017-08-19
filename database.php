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

    $EntityView = new DatabaseView(new Database($Users));

    // VIEW
    if ($Request->query->get('mode') === 'view') {

        $EntityView->Entity->setId($Request->query->get('id'));
        $EntityView->initViewEdit();
        echo $Twig->render('view.html', array(
            'Ev' => $EntityView
        ));
        echo $EntityView->view();

    // EDIT
    } elseif ($Request->query->get('mode') === 'edit') {

        $EntityView->Entity->setId($Request->query->get('id'));
        $EntityView->initViewEdit();
        // check permissions
        $EntityView->Entity->canOrExplode('write');
        // a locked item cannot be edited
        if ($EntityView->Entity->entityData['locked']) {
            throw new Exception(_('<strong>This item is locked.</strong> You cannot edit it.'));
        }

        $Revisions = new Revisions($EntityView->Entity);
        $Uploads = new Uploads($EntityView->Entity);
        $Tags = new Tags($EntityView->Entity);

        echo $Twig->render('edit.html', array(
            'Ev' => $EntityView,
            'Revisions' => $Revisions,
            'Tags' => $Tags,
            'Uploads' => $Uploads,
            'maxUploadSize' => Tools::returnMaxUploadSize()
        ));
        echo $EntityView->buildUploadsHtml();

    // DEFAULT MODE IS SHOW
    } else {
        // CATEGORY FILTER
        if (Tools::checkId($Request->query->get('cat'))) {
            $EntityView->Entity->categoryFilter = "AND items_types.id = " . $Request->query->get('cat');
            $EntityView->searchType = 'category';
        }
        // TAG FILTER
        if ($Request->query->get('tag') != '') {
            $tag = filter_var($Request->query->get('tag'), FILTER_SANITIZE_STRING);
            $EntityView->tag = $tag;
            $EntityView->Entity->tagFilter = "AND tagt.tag LIKE '" . $tag . "'";
            $EntityView->searchType = 'tag';
        }
        // QUERY FILTER
        if ($Request->query->get('q') != '') {
            $query = filter_var($Request->query->get('q'), FILTER_SANITIZE_STRING);
            $EntityView->query = $query;
            $EntityView->Entity->queryFilter = "AND (
                title LIKE '%$query%' OR
                date LIKE '%$query%' OR
                body LIKE '%$query%')";
            $EntityView->searchType = 'query';
        }
        // ORDER
        $order = '';

        // load the pref from the user
        if (isset($EntityView->Entity->Users->userData['orderby'])) {
            $order = $EntityView->Entity->Users->userData['orderby'];
        }

        // now get pref from the filter-order-sort menu
        if ($Request->query->has('order')) {
            $order = $Request->query->get('order');
        }

        if ($order === 'cat') {
            $EntityView->Entity->order = 'items_types.ordering';
        } elseif ($order === 'date' || $order === 'rating' || $order === 'title') {
            $EntityView->Entity->order = 'items.' . $order;
        }

        // SORT
        $sort = '';

        // load the pref from the user
        if (isset($EntityView->Entity->Users->userData['sort'])) {
            $sort = $EntityView->Entity->Users->userData['sort'];
        }

        // now get pref from the filter-order-sort menu
        if ($Request->query->has('sort')) {
            $sort = $Request->query->get('sort');
        }

        if ($sort === 'asc' || $sort === 'desc') {
            $EntityView->Entity->sort = $sort;
        }

        echo $EntityView->buildShowMenu('database');

        // limit the number of items to show if there is no search parameters
        // because with a big database this can be expensive
        if (!$Request->query->has('q') &&
            !$Request->query->has('tag') &&
            !$Request->query->has('filter')) {
            $EntityView->Entity->setLimit(50);
        }

        $ItemsTypes = new ItemsTypes($Users);
        echo $EntityView->buildShow();
        echo $Twig->render('show.html', array(
            'Ev' => $EntityView,
            'Category' => $ItemsTypes
        ));
    }
} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
