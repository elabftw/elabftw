<?php
/**
 * app/controllers/UsersController.php
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
 * Users
 */
try {
    require_once '../../inc/common.php';
    $FormKey = new FormKey();
    $Users = new Users();

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

        if ($Users->update($_POST)) {
            $_SESSION['ok'][] =  _('Configuration updated successfully.');
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

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
    $_SESSION['ko'][] = $e->getMessage();

} finally {
    header('Location: ../../admin.php?tab=' . $tab);
}
