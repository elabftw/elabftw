<?php
/**
 * database.php
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
 * Entry point for database things
 *
 */
require_once 'inc/common.php';
$page_title = _('Database');
$selected_menu = 'Database';
require_once 'inc/head.php';

// add the chemdoodle stuff if we want it
echo addChemdoodle();

try {

    if (!isset($_GET['mode']) || empty($_GET['mode']) || $_GET['mode'] === 'show') {
        $DatabaseView = new DatabaseView(new Database($_SESSION['team_id']));
        $DatabaseView->display = $_SESSION['prefs']['display'];

        // CATEGORY FILTER
        if (isset($_GET['filter']) && !empty($_GET['filter']) && Tools::checkId($_GET['filter'])) {
            $DatabaseView->Database->categoryFilter = "AND items_types.id = " . $_GET['filter'];
            $DatabaseView->searchType = 'filter';
        }
        // TAG FILTER
        if (isset($_GET['tag']) && $_GET['tag'] != '') {
            $tag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
            $DatabaseView->tag = $tag;
            $DatabaseView->Database->tagFilter = "AND items_tags.tag LIKE '" . $tag . "'";
            $DatabaseView->searchType = 'tag';
        }
        // QUERY FILTER
        if (isset($_GET['q']) && !empty($_GET['q'])) {
            $query = filter_var($_GET['q'], FILTER_SANITIZE_STRING);
            $DatabaseView->query = $query;
            $DatabaseView->Database->queryFilter = "AND (title LIKE '%$query%' OR date LIKE '%$query%' OR body LIKE '%$query%')";
            $DatabaseView->searchType = 'query';
        }
        // ORDER
        if (isset($_GET['order'])) {
            if ($_GET['order'] === 'cat') {
                $DatabaseView->Database->order = 'items_types.name';
            } elseif ($_GET['order'] === 'date' || $_GET['order'] === 'rating' || $_GET['order'] === 'title') {
                $DatabaseView->Database->order = 'items.' . $_GET['order'];
            }
        }
        // SORT
        if (isset($_GET['sort'])) {
            if ($_GET['sort'] === 'asc' || $_GET['sort'] === 'desc') {
                $DatabaseView->Database->sort = $_GET['sort'];
            }
        }

        echo $DatabaseView->buildShowMenu('database');

        // limit the number of items to show if there is no search parameters
        // because with a big database this can be expensive
        if (!isset($_GET['q']) && !isset($_GET['tag']) && !isset($_GET['filter'])) {
            $DatabaseView->Database->setLimit(50);
        }
        echo $DatabaseView->buildShow();

    // VIEW
    } elseif ($_GET['mode'] === 'view') {

        $DatabaseView = new DatabaseView(new Database($_SESSION['team_id'], $_GET['id']));
        echo $DatabaseView->view();

    // EDIT
    } elseif ($_GET['mode'] === 'edit') {

        $DatabaseView = new DatabaseView(new Database($_SESSION['team_id'], $_GET['id']));
        echo $DatabaseView->edit();
    }
} catch (Exception $e) {
    display_message('ko', $e->getMessage());
} finally {
    require_once 'inc/footer.php';
}
