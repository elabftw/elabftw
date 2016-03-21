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

    // DESTROY
    if (isset($_POST['formkey'])
        && $formKey->validate()
        && isset($_POST['usersDestroy'])) {

        $tab = 2;

        if ($users->destroy(
            $_POST['usersDestroyEmail'],
            $_POST['usersDestroyPassword']
        )) {
            $msg_arr[] = _('Everything was purged successfully.');
        }
    }

    $_SESSION['ok'] = $msg_arr;
    header('Location: ../../admin.php?tab=' . $tab);

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
    $msg_arr[] = $e->getMessage();
    $_SESSION['ko'] = $msg_arr;
    header('Location: ../../admin.php?tab=' . $tab);
}
