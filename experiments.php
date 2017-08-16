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

/**
 * Entry point for all experiment stuff
 *
 */
try {
    require_once 'app/init.inc.php';
    $pageTitle = ngettext('Experiment', 'Experiments', 2);
    $selectedMenu = 'Experiments';
    require_once 'app/head.inc.php';

    if (!isset($Users)) {
        $Users = new Users($_SESSION['userid']);
    }

    $EntityView = new ExperimentsView(new Experiments($Users));
    $Status = new Status($EntityView->Entity->Users);

    // VIEW
    if ($Request->query->get('mode') === 'view') {

        $EntityView->Entity->setId($Request->query->get('id'));
        $Comments = new Comments($EntityView->Entity);
        $EntityView->initViewEdit();

        $commentsArr = $Comments->read();
        $ownerName = '';
        if ($EntityView->isReadOnly()) {
            // we need to get the fullname of the user who owns the experiment to display the RO message
            $Owner = new Users($EntityView->Entity->entityData['userid']);
            $ownerName = $Owner->userData['fullname'];
        }

        if ($EntityView->Entity->entityData['timestamped']) {
            echo $EntityView->showTimestamp();
        }

        echo $Twig->render('view.html', array(
            'Ev' => $EntityView,
            'Status' => $Status,
            'commentsArr' => $commentsArr,
            'ownerName' => $ownerName,
            'cleanTitle' => $EntityView->getCleanTitle($EntityView->Entity->entityData['title'])
        ));
        echo $EntityView->view();

    // EDIT
    } elseif ($Request->query->get('mode') === 'edit') {

        $EntityView->Entity->setId($Request->query->get('id'));
        $EntityView->initViewEdit();
        // check permissions
        $EntityView->Entity->canOrExplode('write');
        // a locked experiment cannot be edited
        if ($EntityView->Entity->entityData['locked']) {
            throw new Exception(_('<strong>This item is locked.</strong> You cannot edit it.'));
        }

        $Revisions = new Revisions($EntityView->Entity);

        echo $Twig->render('edit.html', array(
            'Ev' => $EntityView,
            'Revisions' => $Revisions,
            'Status' => $Status,
            'cleanTitle' => $EntityView->getCleanTitle($EntityView->Entity->entityData['title'])
        ));
        echo $EntityView->buildUploadsHtml();

    // DEFAULT MODE IS SHOW
    } else {
        // CATEGORY FILTER
        if (Tools::checkId($Request->query->get('cat'))) {
            $EntityView->Entity->categoryFilter = " AND status.id = " . $Request->query->get('cat');
            $EntityView->searchType = 'filter';
        }
        // TAG FILTER
        if ($Request->query->get('tag') != '') {
            $tag = filter_var($Request->query->get('tag'), FILTER_SANITIZE_STRING);
            $EntityView->Entity->tagFilter = " AND tagt.tag LIKE '" . $tag . "'";
            $EntityView->searchType = 'tag';
        }
        // QUERY FILTER
        if ($Request->query->get('q') != '') {
            $query = filter_var($Request->query->get('q'), FILTER_SANITIZE_STRING);
            $EntityView->query = $query;
            $EntityView->Entity->queryFilter = " AND (
                title LIKE '%$query%' OR
                date LIKE '%$query%' OR
                body LIKE '%$query%' OR
                elabid LIKE '%$query%'
            )";
            $EntityView->searchType = 'query';
        }
        // RELATED FILTER
        if (Tools::checkId($Request->query->get('related'))) {
            $EntityView->related = $Request->query->get('related');
            $EntityView->searchType = 'related';
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
            $EntityView->Entity->order = 'status.ordering';
        } elseif ($order === 'date' || $order === 'rating' || $order === 'title') {
            $EntityView->Entity->order = 'experiments.' . $order;
        } elseif ($order === 'comment') {
            $EntityView->Entity->order = 'experiments_comments.recent_comment';
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

        echo $EntityView->buildShowMenu('experiments');
        echo $EntityView->buildShow();
        echo $Twig->render('show.html', array(
            'Ev' => $EntityView,
            'Category' => $Status
        ));
    }

} catch (Exception $e) {
    $debug = false;
    $message = $e->getMessage();
    if ($debug) {
        $message .= ' ' . $e->getFile() . '(' . $e->getLine() . ')';
    }
    echo Tools::displayMessage($message, 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
