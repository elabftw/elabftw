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
        $Users = new Users($Session->get('userid'));
    }

    $Entity = new Experiments($Users);
    $EntityView = new ExperimentsView($Entity);
    $Status = new Status($Entity->Users);

    // VIEW
    if ($Request->query->get('mode') === 'view') {

        $Entity->setId($Request->query->get('id'));
        $Entity->canOrExplode('read');
        $permissions = $Entity->getPermissions();

        // READ ONLY MESSAGE
        $ownerName = '';
        $isReadOnly = false;
        if ($permissions['read'] && !$permissions['write']) {
            // we need to get the fullname of the user who owns the experiment to display the RO message
            $Owner = new Users($Entity->entityData['userid']);
            $ownerName = $Owner->userData['fullname'];
            $isReadOnly = true;
        }

        // TIMESTAMP
        if ($Entity->entityData['timestamped']) {
            echo $EntityView->showTimestamp();
        }
        // UPLOADS
        $UploadsView = new UploadsView(new Uploads($EntityView->Entity));

        // COMMENTS
        $Comments = new Comments($Entity);
        $commentsArr = $Comments->read();

        // RENDER
        echo $Twig->render('view.html', array(
            'Ev' => $EntityView,
            'Entity' => $Entity,
            'Uv' => $UploadsView,
            'Status' => $Status,
            'cleanTitle' => $EntityView->getCleanTitle($EntityView->Entity->entityData['title']),
            'commentsArr' => $commentsArr,
            'mode' => 'view',
            'ownerName' => $ownerName,
            'isReadOnly' => $isReadOnly
        ));

    // EDIT
    } elseif ($Request->query->get('mode') === 'edit') {

        $EntityView->Entity->setId($Request->query->get('id'));
        // check permissions
        $EntityView->Entity->canOrExplode('write');
        // a locked experiment cannot be edited
        if ($EntityView->Entity->entityData['locked']) {
            throw new Exception(_('<strong>This item is locked.</strong> You cannot edit it.'));
        }

        $Revisions = new Revisions($EntityView->Entity);
        // Uploads
        $UploadsView = new UploadsView(new Uploads($EntityView->Entity));
        $TeamGroups = new TeamGroups($Entity->Users);

        echo $Twig->render('edit.html', array(
            'Ev' => $EntityView,
            'Entity' => $Entity,
            'Uv' => $UploadsView,
            'mode' => 'edit',
            'Revisions' => $Revisions,
            'Status' => $Status,
            'TeamGroups' => $TeamGroups,
            'cleanTitle' => $EntityView->getCleanTitle($EntityView->Entity->entityData['title']),
            'maxUploadSize' => Tools::returnMaxUploadSize()
        ));

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
