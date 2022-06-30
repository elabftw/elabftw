<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\AuthenticatedUser;
use Elabftw\Models\Idps;
use Elabftw\Services\SamlAuth;
use Exception;
use OneLogin\Saml2\Auth as SamlAuthLib;
use OneLogin\Saml2\LogoutRequest as SamlLogoutRequest;
use OneLogin\Saml2\LogoutResponse as SamlLogoutResponse;
use OneLogin\Saml2\Settings as SamlSettings;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

require_once 'init.inc.php';

$redirectUrl = '../login.php';

$destroySession = function () use ($App, $Request) {
    if ($App->Users instanceof AuthenticatedUser) {
        $App->Users->invalidateToken();
    }

    // kill session
    $App->Session->invalidate();
    // options to disable a cookie
    $cookieOptions = array(
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
    );
    setcookie('token', '', $cookieOptions);
    setcookie('token_team', '', $cookieOptions);
    setcookie('saml_token', '', $cookieOptions);
    setcookie('icanhazcookies', '', $cookieOptions);
    // if we get redirected by init.inc.php we want to keep this cookie
    // if the user requested logout, remove it
    if (!$Request->query->get('keep_redirect')) {
        setcookie('elab_redirect', '', $cookieOptions);
    }
    setcookie('pdf_sig', '', $cookieOptions);
    setcookie('kickreason', '', $cookieOptions);
};

// now if we are logged in through external auth, hit the external auth url
if ((int) $App->Users->userData['auth_service'] === \Elabftw\Controllers\LoginController::AUTH_EXTERNAL) {
    $redirectUrl = $App->Config->configArr['logout_url'];
    if (empty($redirectUrl)) {
        $redirectUrl = '../login.php';
    }
}

// Try decoding saml information, if available
if ($App->Request->cookies->has('saml_token')) {
    try {
        [$sessionIndex, $idpId] = SamlAuth::decodeToken($App->Request->cookies->get('saml_token'));
    } catch (Exception $e) {
        // log error and show general error message
        $destroySession();  // destroy session anyway

        $App->Log->error('', array('Exception' => $e));
        $template = 'error.html';
        $renderArr = array('error' => Tools::error());
        $Response = new Response();
        $Response->prepare($Request);
        $Response->setContent($App->render($template, $renderArr));
        $Response->send();
    }
}

// check SAML LogoutRequest/Response first
if ($App->Request->query->has('sls') && ($App->Request->query->has('SAMLRequest') || $App->Request->query->has('SAMLResponse'))) {
    $Saml = new Saml($App->Config, new Idps());
    $tmpSettings = $Saml->getSettings(); // get temporary settings to decode message
    if ($App->Request->query->has('SAMLRequest')) {
        $req = new SamlLogoutRequest(new SamlSettings($tmpSettings), $App->Request->query->get('SAMLRequest'));
        $entId = SamlLogoutRequest::getIssuer($req->getXML());
    } else {// if ($App->Request->query->has('SAMLResponse'))
        $resp = new SamlLogoutResponse(new SamlSettings($tmpSettings), $App->Request->query->get('SAMLResponse'));
        $entId = $resp->getIssuer();
    }

    if ($entId === null) {
        $error = 'Could not detect origin of logout message!';
        throw new UnauthorizedException($error);
    }

    $settings = $Saml->getSettingsByEntityId($entId);
    // another IdP should not be able to destroy random sessions
    if (!empty($idpId) && $idpId != $settings['idp_id']) {
        $error = 'Wrong IdP sent Logout Message!';
        throw new UnauthorizedException($error);
    }

    // manually overwrite basepath with basepath + /app, to workaround php-saml#249
    $settings['baseurl'] .= '/app';

    $samlAuthLib = new SamlAuthLib($settings);

    // destroy Session and create response if needed
    $samlRedirectUrl = $samlAuthLib->processSLO(false, null, false, $destroySession, true);

    $errors = $samlAuthLib->getErrors();

    if (!empty($errors)) {
        if (count($errors) === 1 && $errors[0] === 'logout_not_success') {
            // IdP notified us that the logout was not successful, destroy session anyway.

            $destroySession();
        } else {
            $error = Tools::error();
            // get more verbose if debug mode is active
            if ($App->Config->configArr['debug']) {
                $error = implode(', ', $errors);
            }
            throw new UnauthorizedException($error);
        }
    }

    if (!empty($samlRedirectUrl)) { // pass response to IdP
        $redirectUrl = $samlRedirectUrl;
    }
} elseif ($App->Request->cookies->has('saml_token')) {
    // originally logged in using saml, we should try initiating SLO
    try {
        $Saml = new Saml($App->Config, new Idps());
        $settings = $Saml->getSettings($idpId ?? 0);

        // manually overwrite basepath with basepath + /app, to workaround php-saml#249
        $settings['baseurl'] .= '/app';

        $samlAuthLib = new SamlAuthLib($settings);

        // destroy local session in all cases
        $destroySession();
        // do not attempt SLO if no SLO is configured/supported
        if (!empty($settings['idp']['singleLogoutService']['url'])) {
            // initiate SAML SLO
            $samlAuthLib->logout($redirectUrl, array(), null, $sessionIndex ?? null);
            exit;
        }
    } catch (Exception $e) {
        // log error and show general error message
        $destroySession();  // destroy session anyway

        $App->Log->error('', array('Exception' => $e));
        $template = 'error.html';
        $renderArr = array('error' => Tools::error());
        $Response = new Response();
        $Response->prepare($Request);
        $Response->setContent($App->render($template, $renderArr));
        $Response->send();
    }
} else {
    // no SLO, usual logout using destroySession
    $destroySession();
}


// and redirect to login page or ext auth logout url
$Response = new RedirectResponse($redirectUrl);
$Response->send();
