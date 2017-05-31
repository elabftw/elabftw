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

use Exception;
use OneLogin_Saml2_Auth;

session_start();

try {
    if (isset($_GET['acs'])) {

        require_once 'app/init.inc.php';

        $Saml = new Saml(new Config, new Idps);

        $settings = $Saml->getSettings(2);
        $SamlAuth = new OneLogin_Saml2_Auth($settings);

        if (isset($_SESSION) && isset($_SESSION['AuthNRequestID'])) {
            $requestID = $_SESSION['AuthNRequestID'];
        } else {
            $requestID = null;
        }

        $SamlAuth->processResponse($requestID);

        $errors = $SamlAuth->getErrors();

        if (!empty($errors) && $Saml->Config->configArr['debug']) {
            echo "Something went wrong:<br>";
            echo Tools::printArr(implode(', ', $errors));
        }

        if (!$SamlAuth->isAuthenticated()) {
            throw new Exception("Not authenticated!");
        }

        $Auth = new Auth();
        $_SESSION['samlUserdata'] = $SamlAuth->getAttributes();

        // GET EMAIL
        $emailAttribute = $Saml->Config->configArr['saml_email'];
        $email = $_SESSION['samlUserdata'][$emailAttribute];
        if (is_array($email)) {
            $email = $email[0];
        }

        if (!$Auth->loginWithSaml($email)) {
            // the user doesn't exist yet in the db
            // check if the team exists
            $Teams = new Teams();
            $Users = new Users(null, $Saml->Config);

            // GET TEAM
            $teamAttribute = $Saml->Config->configArr['saml_team'];
            $team = $_SESSION['samlUserdata'][$teamAttribute];
            if (is_array($team)) {
                $team = $team[0];
            }
            $teamId = $Teams->initializeIfNeeded($team);

            // GET FIRSTNAME AND LASTNAME
            $firstnameAttribute = $Saml->Config->configArr['saml_firstname'];
            $firstname = $_SESSION['samlUserdata'][$firstnameAttribute];
            if (is_array($firstname)) {
                $firstname = $firstname[0];
            }
            $lastnameAttribute = $Saml->Config->configArr['saml_lastname'];
            $lastname = $_SESSION['samlUserdata'][$lastnameAttribute];
            if (is_array($lastname)) {
                $lastname = $lastname[0];
            }

            // CREATE USER
            $Users->create($email, $teamId, $firstname, $lastname);
            // ok now the user is created, try logging in again
            if (!$Auth->loginWithSaml($email)) {
                throw new Exception("Not authenticated!");
            }
        }

    }
    header('Location: experiments.php');

} catch (Exception $e) {
    echo $e->getMessage();
}
