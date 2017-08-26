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
use Symfony\Component\HttpFoundation\RedirectResponse;

try {
    require_once 'app/init.inc.php';

    if ($Request->query->has('acs')) {

        $Saml = new Saml(new Config, new Idps);

        // TODO this is the id of the idp to use to get the settings
        $settings = $Saml->getSettings(1);
        $SamlAuth = new OneLogin_Saml2_Auth($settings);

        $requestID = null;
        if ($Session->has('AuthNRequestID')) {
            $requestID = $Session->get('AuthNRequestID');
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

        $Session->set('samlUserdata', $SamlAuth->getAttributes());

        // GET EMAIL
        $emailAttribute = $Saml->Config->configArr['saml_email'];
        $email = $Session->get('samlUserdata')[$emailAttribute];
        if (is_array($email)) {
            $email = $email[0];
        }

        if (!$Auth->loginWithSaml($email)) {
            // the user doesn't exist yet in the db
            // check if the team exists
            $Teams = new Teams($Users);
            $Users = new Users(null, $Auth, $Saml->Config);

            // GET TEAM
            $teamAttribute = $Saml->Config->configArr['saml_team'];
            $team = $Session->get('samlUserdata')[$teamAttribute];
            if (is_array($team)) {
                $team = $team[0];
            }
            $teamId = $Teams->initializeIfNeeded($team);

            // GET FIRSTNAME AND LASTNAME
            $firstnameAttribute = $Saml->Config->configArr['saml_firstname'];
            $firstname = $Session->get('samlUserdata')[$firstnameAttribute];
            if (is_array($firstname)) {
                $firstname = $firstname[0];
            }
            $lastnameAttribute = $Saml->Config->configArr['saml_lastname'];
            $lastname = $Session->get('samlUserdata')[$lastnameAttribute];
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
    $Response = new RedirectResponse("experiments.php");
    $Response->send();

} catch (Exception $e) {
    echo $e->getMessage();
}
