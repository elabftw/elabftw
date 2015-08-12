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

if (!isset($_GET['mode']) || empty($_GET['mode']) || $_GET['mode'] === 'show') {
    require_once 'inc/showDB.php';
} elseif ($_GET['mode'] === 'view') {
    require_once 'inc/viewDB.php';
} elseif ($_GET['mode'] === 'edit') {
    require_once 'inc/editDB.php';
} else {
    require_once 'inc/showDB.php';
}

require_once 'inc/footer.php';
