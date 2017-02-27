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

    if (!isset($Users)) {
        $Users = new Users($_SESSION['userid']);
    }

    $EntityView = new DatabaseView(new Database($Users));

    if (!isset($_GET['mode']) || empty($_GET['mode']) || $_GET['mode'] === 'show') {
        // CATEGORY FILTER
        if (isset($_GET['cat']) && !empty($_GET['cat']) && Tools::checkId($_GET['cat'])) {
            $EntityView->Entity->categoryFilter = "AND items_types.id = " . $_GET['cat'];
            $EntityView->searchType = 'category';
        }
        // TAG FILTER
        if (isset($_GET['tag']) && $_GET['tag'] != '') {
            $tag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
            $EntityView->tag = $tag;
            $EntityView->Entity->tagFilter = "AND tagt.tag LIKE '" . $tag . "'";
            $EntityView->searchType = 'tag';
        }
        // QUERY FILTER
        if (isset($_GET['q']) && !empty($_GET['q'])) {
            $query = filter_var($_GET['q'], FILTER_SANITIZE_STRING);
            $EntityView->query = $query;
            $EntityView->Entity->queryFilter = "AND (title LIKE '%$query%' OR date LIKE '%$query%' OR body LIKE '%$query%')";
            $EntityView->searchType = 'query';
        }
        // ORDER
        if (isset($_GET['order'])) {
            if ($_GET['order'] === 'cat') {
                $EntityView->Entity->order = 'items_types.name';
            } elseif ($_GET['order'] === 'date' || $_GET['order'] === 'rating' || $_GET['order'] === 'title') {
                $EntityView->Entity->order = 'items.' . $_GET['order'];
            }
        }
        // SORT
        if (isset($_GET['sort'])) {
            if ($_GET['sort'] === 'asc' || $_GET['sort'] === 'desc') {
                $EntityView->Entity->sort = $_GET['sort'];
            }
        }

        echo $EntityView->buildShowMenu('database');

        // limit the number of items to show if there is no search parameters
        // because with a big database this can be expensive
        if (!isset($_GET['q']) && !isset($_GET['tag']) && !isset($_GET['filter'])) {
            $EntityView->Entity->setLimit(50);
        }
        echo $EntityView->buildShow();
        echo $twig->render('show.html', array(
            'Ev' => $EntityView
        ));

    // VIEW
    } elseif ($_GET['mode'] === 'view') {

        $EntityView->Entity->setId($_GET['id']);
        $EntityView->initViewEdit();
        echo $twig->render('view.html', array(
            'Ev' => $EntityView
        ));
        echo $EntityView->view();

    // EDIT
    } elseif ($_GET['mode'] === 'edit') {

        $EntityView->Entity->setId($_GET['id']);
        $EntityView->initViewEdit();
        // check permissions
        $EntityView->Entity->canOrExplode('write');
        // a locked item cannot be edited
        if ($EntityView->Entity->entityData['locked']) {
            throw new Exception(_('<strong>This item is locked.</strong> You cannot edit it.'));
        }
        $Revisions = new Revisions($EntityView->Entity);
        $Tags = new Tags($EntityView->Entity);
        echo $twig->render('edit.html', array(
            'Ev' => $EntityView,
            'Revisions' => $Revisions,
            'Tags' => $Tags
        ));
        echo $EntityView->buildUploadsHtml();
    }
} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
