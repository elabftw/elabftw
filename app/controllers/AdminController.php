<?php
/**
 * app/controllers/Admin.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Deal with requests sent from the admin page
 *
 */
require_once '../../inc/common.php';

try {
    // UPDATE USERS
    if (isset($_POST['usersUpdate'])) {
        $users = new \Elabftw\Elabftw\Users();
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

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
    $_SESSION['ko'][] = $e->getMessage();
} finally {
    header('Location: ../../admin.php');
}
