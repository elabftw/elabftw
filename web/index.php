<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Idps;
use Elabftw\Models\Teams;
use Exception;
use OneLogin\Saml2\Auth as SamlAuth;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

require_once 'app/init.inc.php';

$Response = new RedirectResponse('experiments.php');

try {
    if ($Request->query->has('acs')) {
        $Saml = new Saml(new Config, new Idps);
        $Teams = new Teams($App->Users);

        $settings = $Saml->getSettings();
        $SamlAuth = new SamlAuth($settings);

        $requestID = null;
        if ($Session->has('AuthNRequestID')) {
            $requestID = $Session->get('AuthNRequestID');
        }

        $SamlAuth->processResponse($requestID);

        $errors = $SamlAuth->getErrors();

        if (!empty($errors) && $Saml->Config->configArr['debug']) {
            echo 'Something went wrong:<br>';
            echo Tools::printArr($errors);
        }

        if (!$SamlAuth->isAuthenticated()) {
            throw new ImproperActionException('Not authenticated!');
        }

        $Session->set('samlUserdata', $SamlAuth->getAttributes());

        // GET EMAIL
        $emailAttribute = $Saml->Config->configArr['saml_email'];
        $email = $Session->get('samlUserdata')[$emailAttribute];
        if (is_array($email)) {
            $email = $email[0];
        }

        if ($email === null) {
            throw new ImproperActionException('Could not find email in response from IDP! Aborting.');
        }

        $userid = $Auth->getUseridFromEmail($email);
        // GET TEAM
        // get attribute from config
        $teamAttribute = $Saml->Config->configArr['saml_team'];

        // several teams can be returned by the IDP
        $teams = $Session->get('samlUserdata')[$teamAttribute];
        // or one but with ',' inside and we'll split on that
        if (\count($teams) === 1) {
            $teams = explode(',', $teams[0]);
        }
        // if no team attribute is sent by the IDP, use the default team
        if (empty($teams)) {
            // we directly get the id from the stored config
            $teamId = (int) $Saml->Config->configArr['saml_team_default'];
            if ($teamId === 0) {
                throw new ImproperActionException('Could not find team ID to assign user!');
            }
            $teams = array((string) $teamId);
        }

        if ($userid === 0) {
            // the user doesn't exist yet in the db

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

            // CREATE USER (and force validation of user)
            $userid = $App->Users->create($email, $teams, $firstname, $lastname, '', null, true);
        }

        // synchronize the teams from the IDP
        // because teams can change since the time the user was created
        if ($Saml->Config->configArr['saml_sync_teams']) {
            $Teams->syncFromIdp($userid, $teams);
        }

        $loginResult = $Auth->login($userid);
        if ($loginResult === true) {
            if ($Request->cookies->has('redirect')) {
                $location = $Request->cookies->get('redirect');
            } else {
                $location = '../../experiments.php';
            }
        } elseif (is_array($loginResult)) {
            $Session->set('team_selection_required', 1);
            $Session->set('auth_userid', $loginResult[0]);
            $Session->set('team_selection', $loginResult[1]);
            $location = 'login.php';
            $Response = new RedirectResponse($location);
        } else {
            throw new ImproperActionException('Could not login!');
        }
    }
} catch (ImproperActionException $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array('Exception' => $e));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
} finally {
    $Response->send();
}
