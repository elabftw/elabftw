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
        $databaseView = new DatabaseView(new Database($_SESSION['team_id']));
        // ORDER
        if (isset($_GET['order'])) {
            if ($_GET['order'] === 'cat') {
                $databaseView->database->order = 'items_types.name';
            } elseif ($_GET['order'] === 'date' || $_GET['order'] === 'rating' || $_GET['order'] === 'title') {
                $databaseView->database->order = 'items.' . $_GET['order'];
            }
        }
        // SORT
        if (isset($_GET['sort'])) {
            if ($_GET['sort'] === 'asc' || $_GET['sort'] === 'desc') {
                $databaseView->database->sort = $_GET['sort'];
            }
        }
        // FILTER
        if (isset($_GET['filter'])) {
            if (Tools::checkId($_GET['filter'])) {
                $databaseView->database->filter = "AND items_types.id = " . $_GET['filter'];
            }
        }
        // TAG
        if (isset($_GET['tag']) && $_GET['tag'] != '') {
            $databaseView->database->tag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
        }
        // for some reason, if it is inside the class, the menu get at bottom of page :/
        // so I made the method public
        // FIXME
        echo $databaseView->buildShowMenu();
        echo $databaseView->show();
        //require_once 'inc/showDB.php';

    // VIEW
    } elseif ($_GET['mode'] === 'view') {

        $databaseView = new DatabaseView(new Database($_SESSION['team_id'], $_GET['id']));
        echo $databaseView->view();

    // EDIT
    } elseif ($_GET['mode'] === 'edit') {

        $databaseView = new DatabaseView(new Database($_SESSION['team_id'], $_GET['id']));
        echo $databaseView->edit();
    }
} catch (Exception $e) {
    display_message('ko', $e->getMessage());
} finally {
    require_once 'inc/footer.php';
}
