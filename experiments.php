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
        require_once 'inc/showXP.php';

    // VIEW
    } elseif ($_GET['mode'] === 'view') {

        $experimentsView = new ExperimentsView(new Experiments($_SESSION['userid'], $_GET['id']));
        echo $experimentsView->view();

    // EDIT
    } elseif ($_GET['mode'] === 'edit') {

        $experimentsView = new ExperimentsView(new Experiments($_SESSION['userid'], $_GET['id']));
        echo $experimentsView->edit();
    }

    require_once 'inc/footer.php';

} catch (Exception $e) {
    display_message('ko', $e->getMessage());
    require_once 'inc/footer.php';
    exit;
}
