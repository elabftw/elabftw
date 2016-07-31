<?php
/**
 * app/controllers/AdminController.php
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
 * Deal with ajax requests sent from the admin page
 *
 */
try {
    require_once '../../app/common.inc.php';

    if (!$_SESSION['is_admin']) {
        throw new Exception('Non admin user tried to access admin panel.');
    }

    $redirect = false;
    $Teams = new Teams();

    // UPDATE TEAM SETTINGS
    if (isset($_POST['teamsUpdateFull'])) {
        $redirect = true;
        if ($Teams->update($_POST, $_SESSION['team_id'])) {
            $_SESSION['ok'][] = _('Configuration updated successfully.');
        } else {
            $_SESSION['ko'][] = _('An error occurred!');
        }
    }

    // UPDATE COMMON TEMPLATE
    if (isset($_POST['commonTplUpdate'])) {
        $Templates = new Templates($_SESSION['team_id']);
        $Templates->update($_POST['commonTplUpdate']);
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
} finally {
    if ($redirect) {
        header('Location: ../../admin.php?tab=1');
    }
}
