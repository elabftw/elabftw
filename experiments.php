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

use \Exception;

/**
 * Entry point for all experiment stuff
 *
 */
require_once 'app/init.inc.php';
$page_title = ngettext('Experiment', 'Experiments', 2);
$selected_menu = 'Experiments';
require_once 'app/head.inc.php';

// add the chemdoodle stuff if we want it
echo addChemdoodle();

try {
    $EntityView = new ExperimentsView(new Experiments(new Users($_SESSION['userid'])));

    if (!isset($_GET['mode']) || empty($_GET['mode']) || $_GET['mode'] === 'show') {
        $EntityView->display = $_SESSION['prefs']['display'];

        // CATEGORY FILTER
        if (isset($_GET['filter']) && !empty($_GET['filter']) && Tools::checkId($_GET['filter'])) {
            $EntityView->Entity->categoryFilter = " AND status.id = " . $_GET['filter'];
            $EntityView->searchType = 'filter';
        }
        // TAG FILTER
        if (isset($_GET['tag']) && $_GET['tag'] != '') {
            $tag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
            $EntityView->Entity->tagFilter = " AND experiments_tags.tag LIKE '" . $tag . "'";
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
        $EntityView->Entity->order = 'experiments.date';
        if (isset($_GET['order'])) {
            if ($_GET['order'] === 'cat') {
                $EntityView->Entity->order = 'status.name';
            } elseif ($_GET['order'] === 'date' || $_GET['order'] === 'rating' || $_GET['order'] === 'title') {
                $EntityView->Entity->order = 'experiments.' . $_GET['order'];
            } elseif ($_GET['order'] === 'comment') {
                $EntityView->Entity->order = 'experiments_comments.datetime';
            }
        }
        // SORT
        if (isset($_GET['sort'])) {
            if ($_GET['sort'] === 'asc' || $_GET['sort'] === 'desc') {
                $EntityView->Entity->sort = $_GET['sort'];
            }
        }

        echo $EntityView->buildShowMenu('experiments');
        echo $EntityView->buildShow();

    // VIEW
    } elseif ($_GET['mode'] === 'view') {

        $EntityView->Entity->setId($_GET['id']);
        echo $EntityView->view();

    // EDIT
    } elseif ($_GET['mode'] === 'edit') {

        $EntityView->Entity->setId($_GET['id']);
        echo $EntityView->edit();
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
