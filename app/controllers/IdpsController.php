<?php
/**
 * app/controllers/IdpsController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Controller for IDPs
 *
 */
try {
    require_once '../../app/init.inc.php';
    $Idps = new Idps();

    if (!$_SESSION['is_sysadmin']) {
        throw new Exception('Non sysadmin user tried to access sysadmin controller.');
    }

    // CREATE IDP
    if (isset($_POST['idpsCreate'])) {
        if ($Idps->create(
            $_POST['name'],
            $_POST['entityid'],
            $_POST['ssoUrl'],
            $_POST['ssoBinding'],
            $_POST['sloUrl'],
            $_POST['sloBinding'],
            $_POST['x509']
        )) {
            $_SESSION['ok'][] = _('Configuration updated successfully.');
        } else {
            $_SESSION['ko'][] = _('An error occurred!');
        }
    }

    // UPDATE IDP
    if (isset($_POST['idpsUpdate'])) {
        if ($Idps->update(
            $_POST['id'],
            $_POST['name'],
            $_POST['entityid'],
            $_POST['ssoUrl'],
            $_POST['ssoBinding'],
            $_POST['sloUrl'],
            $_POST['sloBinding'],
            $_POST['x509']
        )) {
            $_SESSION['ok'][] = _('Configuration updated successfully.');
        } else {
            $_SESSION['ko'][] = _('An error occurred!');
        }
    }

    // DESTROY IDP
    if (isset($_POST['idpsDestroy'])) {
        if ($Idps->destroy($_POST['id'])) {
            $_SESSION['ok'][] = _('Configuration updated successfully.');
        } else {
            $_SESSION['ko'][] = _('An error occurred!');
        }
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
} finally {
    header('Location: ../../sysconfig.php?tab=8');
}
