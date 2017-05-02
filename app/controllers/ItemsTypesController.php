<?php
/**
 * app/controllers/ItemsTypesController.php
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
    $ItemsTypes = new ItemsTypes($Users);

    if (!$_SESSION['is_admin']) {
        throw new Exception('Non admin user tried to access admin panel.');
    }

    // CREATE ITEMS TYPES
    if (isset($_POST['itemsTypesCreate'])) {
        if ($ItemsTypes->create(
            $_POST['name'],
            $_POST['color'],
            $_POST['bookable'],
            $_POST['template']
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

    // UPDATE ITEM TYPE
    if (isset($_POST['itemsTypesUpdate'])) {
        if ($ItemsTypes->update(
            $_POST['id'],
            $_POST['name'],
            $_POST['color'],
            $_POST['bookable'],
            $_POST['template']
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

    // DESTROY ITEM TYPE
    if (isset($_POST['itemsTypesDestroy'])) {
        try {
            $ItemsTypes->destroy($_POST['id']);
            echo json_encode(array(
                'res' => true,
                'msg' => _('Item type deleted successfully')
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
