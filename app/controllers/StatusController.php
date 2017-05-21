<?php
/**
 * app/controllers/StatusController.php
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
 * Deal with ajax requests sent from the admin page
 *
 */
try {
    require_once '../../app/init.inc.php';
    $Status = new Status($Users);

    if (!$_SESSION['is_admin']) {
        throw new Exception('Non admin user tried to access admin panel.');
    }

    // CREATE STATUS
    if (isset($_POST['statusCreate'])) {
        if ($Status->create(
            $_POST['name'],
            $_POST['color'],
            $_POST['isTimestampable']
        )) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // UPDATE STATUS
    if (isset($_POST['statusUpdate'])) {
        if ($Status->update(
            $_POST['id'],
            $_POST['name'],
            $_POST['color'],
            $_POST['isTimestampable'],
            $_POST['isDefault']
        )) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // DESTROY STATUS
    if (isset($_POST['statusDestroy'])) {
        try {
            $Status->destroy($_POST['id']);
            echo json_encode(array(
                'res' => true,
                'msg' => _('Status deleted successfully')
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'res' => false,
                'msg' => $e->getMessage()
            ));
        }
    }
} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
}
