<?php
/**
 * register-exec.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

try {
    require_once '../init.inc.php';

    $Users = new Users(null, new Config);

    // default location to redirect to
    $location = '../../login.php';

    // check for disabled local register
    if ($Users->Config->configArr['local_register'] === '0') {
        throw new Exception('Registration is disabled.');
    }

    // Stop bot registration by checking if the (invisible to humans) bot input is filled
    if (isset($_POST['bot']) && !empty($_POST['bot'])) {
        throw new Exception('Only humans can register an account!');
    }

    if (!isset($_POST['team']) ||
        empty($_POST['team']) ||
        (Tools::checkId($_POST['team']) === false) ||
        !isset($_POST['firstname']) ||
        empty($_POST['firstname']) ||
        !isset($_POST['lastname']) ||
        empty($_POST['lastname']) ||
        !isset($_POST['email']) ||
        empty($_POST['email']) ||
        !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {

        throw new Exception(_('A mandatory field is missing!'));
    }

    //Check whether the query was successful or not
    if (!$Users->create($_POST['email'], $_POST['team'], $_POST['firstname'], $_POST['lastname'], $_POST['password'])) {
        throw new Exception('Failed inserting new account in SQL!');
    }

    if ($Users->needValidation) {
        $_SESSION['ok'][] = _('Registration successful :)<br>Your account must now be validated by an admin.<br>You will receive an email when it is done.');
    } else {
        $_SESSION['ok'][] = _('Registration successful :)<br>Welcome to eLabFTW o/');
    }
    // store the email here so we can put it in the login field
    $_SESSION['email'] = $_POST['email'];

} catch (Exception $e) {
    $_SESSION['ko'][] = $e->getMessage();
    $location = '../../register.php';

} finally {
    header("location: $location");
}
