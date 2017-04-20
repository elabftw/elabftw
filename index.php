<?php
/**
 * index.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use OneLogin_Saml2_Auth;
use OneLogin_Saml2_Utils;

session_start();

if (isset($_GET['acs'])) {

    require_once 'app/init.inc.php';

    $Saml = new Saml(new Idps());

    $settings = $Saml->getSettings(2);
    $SamlAuth = new OneLogin_Saml2_Auth($settings);

    if (isset($_SESSION) && isset($_SESSION['AuthNRequestID'])) {
        $requestID = $_SESSION['AuthNRequestID'];
    } else {
        $requestID = null;
    }

    $SamlAuth->processResponse($requestID);

    $errors = $SamlAuth->getErrors();

    if (!empty($errors)) {
        print_r('<p>'.implode(', ', $errors).'</p>');
    }

    if (!$SamlAuth->isAuthenticated()) {
        echo "<p>Not authenticated</p>";
        exit();
    }

    $Auth = new Auth();
    $_SESSION['samlUserdata'] = $SamlAuth->getAttributes();
    if ($Auth->loginWithSaml($_SESSION['samlUserdata']['User.email'][0])) {

        header('Location: experiments.php');
    } else {
        echo 'No user with this email';
    }

} else {
    /**
     * As there is nothing to show on the index page, we go to the experiments page directly
     *
     */
    header('Location: experiments.php');
}
