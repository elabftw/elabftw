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

/**
 * Entry point for all experiment stuff
 *
 */
require_once 'inc/common.php';
$page_title = ngettext('Experiment', 'Experiments', 2);
$selected_menu = 'Experiments';
require_once 'inc/head.php';

// add the chemdoodle stuff if we want it
if (isset($_SESSION) && $_SESSION['prefs']['chem_editor']) {
    ?>
    <link rel="stylesheet" href="css/chemdoodle.css" type="text/css">
    <script src="js/chemdoodle.js"></script>
    <script src="js/chemdoodle-uis.js"></script>
    <script>
        ChemDoodle.iChemLabs.useHTTPS();
    </script>
    <?php
}

if (!isset($_GET['mode']) || (empty($_GET['mode'])) || ($_GET['mode'] === 'show')) {
    require_once 'inc/showXP.php';
} elseif ($_GET['mode'] === 'view') {
    require_once 'inc/viewXP.php';
} elseif ($_GET['mode'] === 'edit') {
    require_once 'inc/editXP.php';
} else {
    require_once 'inc/showXP.php';
}

require_once 'inc/footer.php';
