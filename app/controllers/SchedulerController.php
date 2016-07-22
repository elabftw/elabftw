<?php
/**
 * app/controllers/SchedulerController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Controller for the scheduler
 *
 */
try {
    require_once '../../inc/common.php';
    $Scheduler = new Scheduler($_SESSION['team_id']);

    // CREATE
    if (isset($_POST['create'])) {
        $Scheduler->setId($_POST['item']);
        if ($Scheduler->create($_POST['date'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
}
