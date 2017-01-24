<?php
/**
 * app/controllers/UsersController.php
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
 * Users infos from admin page
 */
$redirect = true;

try {
    require_once '../../app/init.inc.php';

    if (!$_SESSION['is_admin']) {
        throw new Exception('Non admin user tried to access admin panel.');
    }

    $FormKey = new FormKey();
    $Users = new Users(null, new Config);

    $tab = 1;
    $location = '../../admin.php?tab=' . $tab;

    // VALIDATE
    if (isset($_POST['usersValidate'])) {
        $tab = 2;
        // loop the array
        foreach ($_POST['usersValidateIdArr'] as $userid) {
            $_SESSION['ok'][] = $Users->validate($userid);
        }
    }

    // UPDATE USERS
    if (isset($_POST['usersUpdate'])) {
        $tab = 2;
        if (isset($_POST['fromSysconfig'])) {
            $location = "../../sysconfig.php?tab=$tab";
        } else {
            $location = "../../admin.php?tab=$tab";
        }

        if ($Users->update($_POST)) {
            $_SESSION['ok'][] = _('Configuration updated successfully.');
        }
    }

    // DESTROY
    if (isset($_POST['formkey'])
        && $FormKey->validate()
        && isset($_POST['usersDestroy'])) {

        $tab = 2;

        if ($Users->destroy(
            $_POST['usersDestroyEmail'],
            $_POST['usersDestroyPassword']
        )) {
            $_SESSION['ok'][] = _('Everything was purged successfully.');
        }
    }

    // (RE)GENERATE AN API KEY
    if (isset($_POST['generateApiKey'])) {
        $redirect = false;
        $Users->setId($_SESSION['userid']);
        if ($Users->generateApiKey()) {
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


} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
    $_SESSION['ko'][] = Tools::error();

} finally {
    if ($redirect) {
        header("Location: $location");
    }
}
