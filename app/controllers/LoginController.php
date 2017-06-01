<?php
/**
 * LoginController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use OneLogin_Saml2_Auth;

try {
    require_once '../init.inc.php';

    // default location for redirect
    $location = '../../login.php';

    $formKey = new FormKey();
    $Auth = new Auth();
    $Saml = new Saml(new Config, new Idps);

    if (isset($_POST['idp_id'])) { // login with SAML
        $settings = $Saml->getSettings($_POST['idp_id']);
        $auth = new OneLogin_Saml2_Auth($settings);
        $returnUrl = $settings['baseurl'] . "/index.php?acs&idp=" . $_POST['idp_id'];
        $auth->login($returnUrl);

    } else {

        // FORMKEY
        if (!isset($_POST['formkey']) || !$formKey->validate()) {
            throw new Exception(_("Your session expired. Please retry."));
        }

        // EMAIL
        if ((!isset($_POST['email'])) || (empty($_POST['email']))) {
            throw new Exception(_('A mandatory field is missing!'));
        }

        // PASSWORD
        if ((!isset($_POST['password'])) || (empty($_POST['password']))) {
            throw new Exception(_('A mandatory field is missing!'));
        }

        // this is here to avoid an "Undefined index" notice
        if (isset($_POST['rememberme'])) {
            $rememberme = $_POST['rememberme'];
        } else {
            $rememberme = 'off';
        }

        // the actual login
        if ($Auth->login($_POST['email'], $_POST['password'], $rememberme)) {
            if (isset($_COOKIE['redirect'])) {
                $location = $_COOKIE['redirect'];
            } else {
                $location = '../../experiments.php';
            }
        } else {
            // log the attempt if the login failed
            $Logs = new Logs();
            $Logs->create('Warning', $_SERVER['REMOTE_ADDR'], 'Failed login attempt');
            // inform the user
            $_SESSION['ko'][] = _("Login failed. Either you mistyped your password or your account isn't activated yet.");
            if (!isset($_SESSION['failed_attempt'])) {
                $_SESSION['failed_attempt'] = 1;
            } else {
                $_SESSION['failed_attempt'] += 1;
            }
        }
    }

} catch (Exception $e) {
    $_SESSION['ko'][] = $e->getMessage();

} finally {
    header("location: $location");
}
