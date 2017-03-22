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

session_start();
if (isset($_GET['acs'])) {

    require_once 'vendor/onelogin/php-saml/_toolkit_loader.php';

    require_once 'app/init.inc.php';

    $Saml = new Saml();

    $settings = $Saml->getSettings();
    $auth = new \OneLogin_Saml2_Auth($settings);

    if (isset($_SESSION) && isset($_SESSION['AuthNRequestID'])) {
        $requestID = $_SESSION['AuthNRequestID'];
    } else {
        $requestID = null;
    }

    $auth->processResponse($requestID);

    $errors = $auth->getErrors();

    if (!empty($errors)) {
        print_r('<p>'.implode(', ', $errors).'</p>');
    }

    if (!$auth->isAuthenticated()) {
        echo "<p>Not authenticated</p>";
        exit();
    }

    $_SESSION['samlUserdata'] = $auth->getAttributes();
    $_SESSION['samlNameId'] = $auth->getNameId();
    $_SESSION['samlNameIdFormat'] = $auth->getNameIdFormat();
    $_SESSION['samlSessionIndex'] = $auth->getSessionIndex();
    unset($_SESSION['AuthNRequestID']);
    if (isset($_POST['RelayState']) && OneLogin_Saml2_Utils::getSelfURL() != $_POST['RelayState']) {
        $auth->redirectTo($_POST['RelayState']);
    }

    if (!empty($errors)) {
        print_r('<p>'.implode(', ', $errors).'</p>');
        exit();
    }

    if (!$auth->isAuthenticated()) {
        echo "<p>Not authenticated</p>";
        exit();
    }

    $attributes = $_SESSION['samlUserdata'];

    if (!empty($attributes)) {
        echo '<h1>'._('User attributes:').'</h1>';
        echo '<table><thead><th>'._('Name').'</th><th>'._('Values').'</th></thead><tbody>';
        foreach ($attributes as $attributeName => $attributeValues) {
            echo '<tr><td>'.htmlentities($attributeName).'</td><td><ul>';
            foreach ($attributeValues as $attributeValue) {
                echo '<li>'.htmlentities($attributeValue).'</li>';
            }
            echo '</ul></td></tr>';
        }
        echo '</tbody></table>';
        if (!empty($_SESSION['IdPSessionIndex'])) {
            echo '<p>The SessionIndex of the IdP is: '.$_SESSION['IdPSessionIndex'].'</p>';
        }
    } else {
        echo _('Attributes not found');
    }
} else {
    /**
     * As there is nothing to show on the index page, we go to the experiments page directly
     *
     */
    header('Location: experiments.php');
}
