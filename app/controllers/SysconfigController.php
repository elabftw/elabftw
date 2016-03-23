<?php
/**
 * app/controllers/SysconfigController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

/**
 * Deal with ajax requests sent from the sysconfig page
 *
 */
require_once '../../inc/common.php';

try {
    $sysconfig = new Sysconfig();
    // the constructor will check for sysadmin rights
    $teams = new Teams();

    // CREATE
    if (isset($_POST['teamsCreate'])) {
        if ($teams->create($_POST['teamsName'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // UPDATE
    if (isset($_POST['teamsUpdate'])) {
        if ($teams->updateName($_POST['teamsUpdateId'], $_POST['teamsUpdateName'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // DESTROY
    if (isset($_POST['teamsDestroy'])) {
        if ($teams->destroy($_POST['teamsDestroyId'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // SEND TEST EMAIL
    if (isset($_POST['testemailSend'])) {
        if ($sysconfig->testemailSend($_POST['testemailEmail'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
}
