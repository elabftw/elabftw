<?php
/**
 * app/controllers/TeamsController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Deal with ajax requests sent from the sysconfig page
 *
 */
require_once '../../inc/common.php';

// the constructor will check for sysadmin rights
try {
    $teams = new \Elabftw\Elabftw\Teams();

    // CREATE
    if (isset($_POST['teamsCreate'])) {
        $teams->create($_POST['teamsName']);
    }

    // UPDATE
    if (isset($_POST['teamsUpdate'])) {
        $teams->update($id, $_POST['teamUpdateName']);
    }

    // DESTROY
    if (isset($_POST['teamsDestroy'])) {
        if ($teams->destroy($_POST['teamsDestroyId'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
}
