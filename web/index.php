<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Auth\Saml as SamlAuth;
use Elabftw\Enums\Entrypoint;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Idps;
use Elabftw\Services\LoginHelper;
use Exception;
use OneLogin\Saml2\Auth as SamlAuthLib;
use OneLogin\Saml2\Response as SamlResponse;
use OneLogin\Saml2\Settings as SamlSettings;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

require_once 'app/init.inc.php';

try {
    // Note: this code should be in logincontroller!

    // SAML: IDP will redirect to this page after user login on IDP website
    if ($App->Request->query->has('acs') && $App->Request->request->has('SAMLResponse')) {
        $IdpsHelper = new IdpsHelper($App->Config, new Idps($App->Users));
        $tmpSettings = $IdpsHelper->getSettings(); // get temporary settings to decode message
        $resp = new SamlResponse(new SamlSettings($tmpSettings), $App->Request->request->getString('SAMLResponse'));
        $entId = $resp->getIssuers()[0]; // getIssuers returns always one or two entity ids

        $settings = $IdpsHelper->getSettingsByEntityId($entId);
        $idpId = $settings['idp_id'];
        $AuthService = new SamlAuth(new SamlAuthLib($settings), $App->Config->configArr, $settings);

        $AuthResponse = $AuthService->assertIdpResponse();
        $LoginHelper = new LoginHelper($AuthResponse, $App->Session);

        // the sysconfig option to allow users to set an auth cookie is the
        // only toggle for saml login setting cookies or not
        $rememberMe = $App->Config->configArr['remember_me_allowed'] === '1';

        // save IdP id and session idx for proper logout
        $cookieOptions = array(
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None',
        );
        $sessOptions = session_get_cookie_params();

        if ($rememberMe) {
            $cookieOptions['expires'] = $LoginHelper->getExpires();
        } elseif ($sessOptions['lifetime'] > 0) {
            $cookieOptions['expires'] = time() + $sessOptions['lifetime'];
        }

        setcookie('saml_token', $AuthService->encodeToken($idpId), $cookieOptions);

        // no team was found so user must select one
        if ($AuthResponse->initTeamRequired) {
            $App->Session->set('initial_team_selection_required', true);
            $App->Session->set('teaminit_email', $AuthResponse->initTeamUserInfo['email']);
            $App->Session->set('teaminit_firstname', $AuthResponse->initTeamUserInfo['firstname']);
            $App->Session->set('teaminit_lastname', $AuthResponse->initTeamUserInfo['lastname']);
            $App->Session->set('teaminit_orgid', $AuthResponse->initTeamUserInfo['orgid']);
            $location = '/login.php';

            // if the user is in several teams, we need to redirect to the team selection
        } elseif ($AuthResponse->isInSeveralTeams) {
            $App->Session->set('team_selection_required', true);
            $App->Session->set('team_selection', $AuthResponse->selectableTeams);
            $App->Session->set('auth_userid', $AuthResponse->userid);
            $location = '/login.php';

        } elseif ($AuthResponse->isValidated === false) {
            // send a helpful message if account requires validation, needs to be after team selection
            throw new ImproperActionException(_('Your account is not validated. An admin of your team needs to validate it!'));
        } else {
            $LoginHelper->login($rememberMe);
            // we redirect on the same page but this time we will be auth and it will redirect us to the correct location
            $location = '/index.php';
        }
        // the redirect cookie is ignored for saml auth. See #2438.
        // we don't use a RedirectResponse but show a temporary redirection page or it will not work properly
        echo "<html><head><meta http-equiv='refresh' content='1;url=$location' /><title>You are being redirected...</title></head><body>You are being redirected...</body></html>";
        exit;
    }
    $location = '/' . (Entrypoint::tryFrom($App->Users->userData['entrypoint'] ?? 0) ?? Entrypoint::Dashboard)->toPage();
    $Response = new RedirectResponse($location);
    $Response->send();
} catch (ImproperActionException $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array('Exception' => $e));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
