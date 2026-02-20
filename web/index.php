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

use Elabftw\Auth\MfaGate;
use Elabftw\Auth\Saml as SamlAuth;
use Elabftw\Enums\EnforceMfa;
use Elabftw\Enums\Entrypoint;
use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Idps;
use Elabftw\Params\UserParams;
use Elabftw\Services\LoginHelper;
use Exception;
use OneLogin\Saml2\Auth as SamlAuthLib;
use OneLogin\Saml2\Response as SamlResponse;
use OneLogin\Saml2\Settings as SamlSettings;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

require_once 'app/init.inc.php';

$Response = new Response();

try {
    // Note: this code should be in logincontroller!

    // SAML: IDP will redirect to this page after user login on IDP website
    if ($App->Request->query->has('acs') && $App->Request->request->has('SAMLResponse')) {
        $IdpsHelper = new IdpsHelper($App->Config, new Idps($App->Users));
        $tmpSettings = $IdpsHelper->getSettings(); // get temporary settings to decode message
        $resp = new SamlResponse(new SamlSettings($tmpSettings), $App->Request->request->getString('SAMLResponse'));
        $issuers = $resp->getIssuers(); // getIssuers returns one or two entity ids as an array
        if (empty($issuers)) {
            throw new ImproperActionException('Could not find an Issuer in the response sent by the IdP!');
        }
        // use the first issuer found in the response
        $settings = $IdpsHelper->getSettingsByEntityId($issuers[0]);
        $idpId = $settings['idp_id'];
        $AuthService = new SamlAuth(new SamlAuthLib($settings), $App->Config->configArr, $settings);

        $AuthResponse = $AuthService->assertIdpResponse();

        // START copy pasta from LoginController: there is still more work to be done to improve all this code...
        // no team was found so user must select one
        if ($AuthResponse->initTeamRequired()) {
            $info = $AuthResponse->getInitTeamInfo();
            // TODO store the array directly!
            $App->Session->set('initial_team_selection_required', true);
            $App->Session->set('teaminit_email', $info['email'] ?? '');
            $App->Session->set('teaminit_firstname', $info['firstname'] ?? '');
            $App->Session->set('teaminit_lastname', $info['lastname'] ?? '');
            $App->Session->set('teaminit_orgid', $info['orgid'] ?? '');
            $location = '/login.php';
            $Response = new RedirectResponse($location);
            $Response->send();
            exit;
        }

        $loggingInUser = $AuthResponse->getUser();

        /////////
        // MFA
        // check if we need to do mfa auth too after a first successful authentication
        // if we're receiving mfa_secret, it's because we just enabled MFA, so save it for that user
        if ($App->Request->request->has('mfa_secret')) {
            $loggingInUser->update(new UserParams('mfa_secret', $App->Request->request->getString('mfa_secret')));
        }
        $enforceMfa = EnforceMfa::from((int) $App->Config->configArr['enforce_mfa']);
        // MFA can be required because the user has mfa_secret or because it is enforced for their level
        if (MfaGate::isMfaRequired($enforceMfa, $loggingInUser)) {
            if ($AuthResponse->hasVerifiedMfa()) {
                $App->Session->remove('mfa_auth_required');
                $App->Session->remove('mfa_secret');
            } else {
                $App->Session->set('mfa_auth_required', true);
                // remember which user is authenticated in the Session
                $App->Session->set('auth_userid', $AuthResponse->getAuthUserid());
                $location = '/login.php';
                echo "<html><head><meta http-equiv='refresh' content='1;url=$location' /><title>You are being redirected...</title></head><body>You are being redirected...</body></html>";
                exit;
            }
        }
        // END copy pasta from LoginController

        $LoginHelper = new LoginHelper($AuthResponse, $App->Session, (int) $App->Config->configArr['cookie_validity_time']);

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
            $cookieOptions['expires'] = $LoginHelper->getCookieExpiryTimestamp();
        } elseif ($sessOptions['lifetime'] > 0) {
            $cookieOptions['expires'] = time() + $sessOptions['lifetime'];
        }

        setcookie('saml_token', $AuthService->encodeToken($idpId), $cookieOptions);

        // if the user is in several teams, we need to redirect to the team selection
        if ($AuthResponse->isInSeveralTeams()) {
            $App->Session->set('team_selection_required', true);
            $App->Session->set('team_selection', $AuthResponse->getSelectableTeams());
            $App->Session->set('auth_userid', $AuthResponse->getAuthUserid());
            $location = '/login.php';

        } elseif ($loggingInUser->userData['validated'] === 0) {
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
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
