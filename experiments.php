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
    $Tags = new Tags($EntityView->Entity);

    if (!isset($_GET['mode']) || empty($_GET['mode']) || $_GET['mode'] === 'show') {
        // CATEGORY FILTER
        if (isset($_GET['cat']) && !empty($_GET['cat']) && Tools::checkId($_GET['cat'])) {
            $EntityView->Entity->categoryFilter = " AND status.id = " . $_GET['cat'];
            $EntityView->searchType = 'filter';
        }
        // TAG FILTER
        if (isset($_GET['tag']) && $_GET['tag'] != '') {
            $tag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
            $EntityView->Entity->tagFilter = " AND tagt.tag LIKE '" . $tag . "'";
            $EntityView->searchType = 'tag';
        }
        // QUERY FILTER
        if (isset($_GET['q']) && !empty($_GET['q'])) {
            $query = filter_var($_GET['q'], FILTER_SANITIZE_STRING);
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
        if (isset($_GET['related']) && Tools::checkId($_GET['related'])) {
            $EntityView->related = $_GET['related'];
            $EntityView->searchType = 'related';
        }
        // ORDER
        // default by date
        $order = 'date';

        // load the pref from the user
        if (isset($EntityView->Entity->Users->userData['orderby'])) {
            $order = $EntityView->Entity->Users->userData['orderby'];
        }

        // now GET pref from the filter-order-sort menu
        if (isset($_GET['order']) && strlen($_GET['order']) > 1) {
            $order = $_GET['order'];
        }

        if ($order === 'cat') {
            $EntityView->Entity->order = 'status.ordering';
        } elseif ($order === 'date' || $order === 'rating' || $order === 'title') {
            $EntityView->Entity->order = 'experiments.' . $order;
        } elseif ($order === 'comment') {
            $EntityView->Entity->order = 'experiments_comments.recentComment';
        }

        // SORT
        $sort = 'desc';

        // load the pref from the user
        if (isset($EntityView->Entity->Users->userData['sort'])) {
            $sort = $EntityView->Entity->Users->userData['sort'];
        }

        // now GET pref from the filter-order-sort menu
        if (isset($_GET['sort']) && strlen($_GET['sort']) > 1) {
            $sort = $_GET['sort'];
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

    // VIEW
    } elseif ($_GET['mode'] === 'view') {

        $EntityView->Entity->setId($_GET['id']);
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
            'Tags' => $Tags,
            'commentsArr' => $commentsArr,
            'ownerName' => $ownerName,
            'cleanTitle' => $EntityView->getCleanTitle($EntityView->Entity->entityData['title'])
        ));
        echo $EntityView->view();

    // EDIT
    } elseif ($_GET['mode'] === 'edit') {

        $EntityView->Entity->setId($_GET['id']);
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
            'Tags' => $Tags,
            'cleanTitle' => $EntityView->getCleanTitle($EntityView->Entity->entityData['title'])
        ));
        echo $EntityView->buildUploadsHtml();
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
