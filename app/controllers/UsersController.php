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

/**
 * Users
 */
require_once '../../inc/common.php';
$formKey = new \Elabftw\Elabftw\FormKey();

try {
    $users = new \Elabftw\Elabftw\Users();

    // VALIDATE
    if (isset($_POST['usersValidate'])) {
        $tab = 2;
        // loop the array
        foreach ($_POST['usersValidateIdArr'] as $userid) {
            $_SESSION['ok'][] = $users->validate($userid);
        }
    }


    // UPDATE USERS
    if (isset($_POST['usersUpdate'])) {
        $tab = 2;

        if ($users->update(
            $_POST['usersUpdateId'],
            $_POST['usersUpdateFirstname'],
            $_POST['usersUpdateLastname'],
            $_POST['usersUpdateUsername'],
            $_POST['usersUpdateEmail'],
            $_POST['usersUpdateValidated'],
            $_POST['usersUpdateUsergroup'],
            $_POST['usersUpdatePassword']
        )) {
            $_SESSION['ok'][] =  _('Configuration updated successfully.');
        }
    }

    // DESTROY
    if (isset($_POST['formkey'])
        && $formKey->validate()
        && isset($_POST['usersDestroy'])) {

        $tab = 2;

        if ($users->destroy(
            $_POST['usersDestroyEmail'],
            $_POST['usersDestroyPassword']
        )) {
            $_SESSION['ok'][] = _('Everything was purged successfully.');
        }
    }
} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
    $_SESSION['ko'][] = $e->getMessage();
} finally {
    header('Location: ../../admin.php?tab=' . $tab);
}
