<?php
/**
 * app/controllers/StatusController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

/**
 * Deal with ajax requests sent from the admin page
 *
 */
require_once '../../inc/common.php';

// the constructor will check for admin rights
try {
    $status = new Status();

    // CREATE STATUS
    if (isset($_POST['statusCreate'])) {
        if ($status->create(
            $_POST['statusName'],
            $_POST['statusColor'],
            $_SESSION['team_id']
        )) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // UPDATE STATUS
    if (isset($_POST['statusUpdate'])) {
        if ($status->update(
            $_POST['statusId'],
            $_POST['statusName'],
            $_POST['statusColor'],
            $_POST['statusDefault'],
            $_SESSION['team_id']
        )) {
            echo '1';
        } else {
            echo '0';
        }
    }

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
}
