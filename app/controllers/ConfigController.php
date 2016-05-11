<?php
/**
 * app/controllers/ConfigController.php
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
 * Deal with ajax requests sent from the sysconfig page
 *
 */
try {
    require_once '../../inc/common.php';
    $Teams = new Teams();
    $redirect = false;

    // PROMOTE SYSADMIN
    if (isset($_POST['promoteSysadmin'])) {
        $Users = new Users();
        if ($Users->promoteSysadmin($_POST['email'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // CREATE TEAM
    if (isset($_POST['teamsCreate'])) {
        if ($Teams->create($_POST['teamsName'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // UPDATE TEAM NAME
    if (isset($_POST['teamsUpdate'])) {
        if ($Teams->updateName($_POST['teamsUpdateId'], $_POST['teamsUpdateName'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // DESTROY TEAM
    if (isset($_POST['teamsDestroy'])) {
        if ($Teams->destroy($_POST['teamsDestroyId'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // UPDATE TEAM
    if (isset($_POST['teamsUpdateFull'])) {
        $redirect = true;
        if ($Teams->update($_POST)) {
            $_SESSION['ok'][] = _('Configuration updated successfully!');
        } else {
            $_SESSION['ko'][] = _('An error occurred!');
        }
    }

    // UPDATE COMMON TEMPLATE
    if (isset($_POST['commonTplUpdate'])) {
        $Templates = new Templates($_SESSION['team_id']);
        $Templates->update($_POST['commonTplUpdate']);
    }

    // SEND TEST EMAIL
    if (isset($_POST['testemailSend'])) {
        $Sysconfig = new Sysconfig();
        if ($Sysconfig->testemailSend($_POST['testemailEmail'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // DESTROY LOGS
    if (isset($_POST['logsDestroy'])) {
        $Logs = new Logs();
        if ($Logs->destroy()) {
            echo '1';
        } else {
            echo '0';
        }
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
    $_SESSION['ko'][] = Tools::error();
} finally {
    if ($redirect) {
        header('Location: ../../admin.php?tab=1');
    }
}
