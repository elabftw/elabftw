<?php
/**
 * app/controllers/ExperimentsController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Experiments
 *
 */
require_once '../../inc/common.php';

try {
    $experiments = new \Elabftw\Elabftw\Experiments();

    // UPDATE STATUS
    if (isset($_POST['experimentsUpdateStatus'])) {
        $experiments->updateStatus(
            $_POST['experimentsUpdateStatusId'],
            $_POST['experimentsUpdateStatusStatus'],
            $_SESSION['userid']
        );
    }

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
}
