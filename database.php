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
        require_once 'inc/showDB.php';

    // VIEW
    } elseif ($_GET['mode'] === 'view') {

        $databaseView = new DatabaseView(new Database($_SESSION['team_id'], $_GET['id']));
        echo $databaseView->view();

    // EDIT
    } elseif ($_GET['mode'] === 'edit') {

        $databaseView = new DatabaseView(new Database($_SESSION['team_id'], $_GET['id']));
        echo $databaseView->edit();
    }

    require_once 'inc/footer.php';

} catch (Exception $e) {
    display_message('ko', $e->getMessage());
    require_once 'inc/footer.php';
    exit;
}
