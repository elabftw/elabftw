<?php
/**
 * experiments.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use \Exception;

/**
 * Entry point for all experiment stuff
 *
 */
require_once 'inc/common.php';
$page_title = ngettext('Experiment', 'Experiments', 2);
$selected_menu = 'Experiments';
require_once 'inc/head.php';

// add the chemdoodle stuff if we want it
echo addChemdoodle();

try {

    if (!isset($_GET['mode']) || empty($_GET['mode']) || $_GET['mode'] === 'show') {
        $experimentsView = new ExperimentsView(new Experiments($_SESSION['userid']));
        $experimentsView->display = $_SESSION['prefs']['display'];

        // CATEGORY FILTER
        if (isset($_GET['filter']) && !empty($_GET['filter']) && Tools::checkId($_GET['filter'])) {
            $experimentsView->experiments->categoryFilter = "AND status.id = " . $_GET['filter'];
            $experimentsView->searchType = 'filter';
        }
        // TAG FILTER
        if (isset($_GET['tag']) && $_GET['tag'] != '') {
            $tag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
            $experimentsView->tag = $tag;
            $experimentsView->experiments->tagFilter = "AND experiments_tags.tag LIKE '" . $tag . "'";
            $experimentsView->searchType = 'tag';
        }
        // QUERY FILTER
        if (isset($_GET['q']) && !empty($_GET['q'])) {
            $query = filter_var($_GET['q'], FILTER_SANITIZE_STRING);
            $experimentsView->query = $query;
            $experimentsView->experiments->queryFilter = "AND (title LIKE '%$query%' OR date LIKE '%$query%' OR body LIKE '%$query%')";
            $experimentsView->searchType = 'query';
        }
        // RELATED FILTER
        if (isset($_GET['related']) && Tools::checkId($_GET['related'])) {
            $experimentsView->related = $_GET['related'];
            $experimentsView->searchType = 'related';
        }
        // ORDER
        if (isset($_GET['order'])) {
            if ($_GET['order'] === 'cat') {
                $experimentsView->experiments->order = 'status.name';
            } elseif ($_GET['order'] === 'date' || $_GET['order'] === 'rating' || $_GET['order'] === 'title') {
                $experimentsView->experiments->order = 'experiments.' . $_GET['order'];
            }
        }
        // SORT
        if (isset($_GET['sort'])) {
            if ($_GET['sort'] === 'asc' || $_GET['sort'] === 'desc') {
                $experimentsView->experiments->sort = $_GET['sort'];
            }
        }

        echo $experimentsView->buildShowMenu('experiments');
        echo $experimentsView->buildShow();

    // VIEW
    } elseif ($_GET['mode'] === 'view') {

        $experimentsView = new ExperimentsView(new Experiments($_SESSION['userid'], $_GET['id']));
        echo $experimentsView->view();

    // EDIT
    } elseif ($_GET['mode'] === 'edit') {

        $experimentsView = new ExperimentsView(new Experiments($_SESSION['userid'], $_GET['id']));
        echo $experimentsView->edit();
    }

} catch (Exception $e) {
    display_message('ko', $e->getMessage());
} finally {
    require_once 'inc/footer.php';
}
