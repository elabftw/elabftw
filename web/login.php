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

use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Idps;
use Elabftw\Models\Users\Users;
use Elabftw\Services\MfaHelper;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Login page
 */
require_once 'app/init.inc.php';

$Response = new Response();
$Response->prepare($App->Request);

try {
    // if we are not in https, die saying we work only in https
    if (!$App->Request->isSecure() && !$App->Request->server->has('HTTP_X_FORWARDED_PROTO')) {
        // get the url to display a link to click
        $url = Env::asString('SITE_URL');
        $message = "eLabFTW works only in HTTPS. Please enable HTTPS on your server or ensure X-Forwarded-Proto header is correctly sent by the load balancer. Or follow this link : <a href='" .
            $url . "'>$url</a>";
        throw new ImproperActionException($message);
    }

    if ($App->Request->query->has('rm_teaminit')) {
        $App->Session->remove('teaminit_done');
    }

    // if user already has an mfa_secret, we ask for it, otherwise they will be shown the QR code
    if ($App->Session->has('mfa_auth_required')) {

        $halfLoggedInUser = new Users($App->Session->get('auth_userid'));
        $mfaQRCodeImageDataUri = '';
        $mfaNewSecret = '';

        $App->Session->set('mfa_secret', $halfLoggedInUser->userData['mfa_secret']);
        // if no secret exists already, we will need to display a qrcode
        if ($halfLoggedInUser->userData['mfa_secret'] === null) {
            $MfaHelper = new MfaHelper();
            $mfaQRCodeImageDataUri = $MfaHelper->getQRCodeImageAsDataUri($halfLoggedInUser->userData['email']);
            $mfaNewSecret = $MfaHelper->secret;
        }

        $Response->setContent($App->render('mfa.html', array(
            'hideTitle' => true,
            // the title is hidden in the page, but give it nonetheless for the document.title
            'pageTitle' => _('Two Factor Authentication'),
            'mfaQRCodeImageDataUri' => $mfaQRCodeImageDataUri,
            'mfaNewSecret' => $mfaNewSecret,
        )));
        $Response->send();
        exit;
    }

    if ($App->Request->query->get('switch_team') === '1') {
        $loggedInUser = new Users($App->Session->get('userid'));
        $App->Session->set('team_selection_required', true);
        $App->Session->set('team_selection', $loggedInUser->userData['teams']);
        $App->Session->set('auth_userid', $loggedInUser->userData['userid']);
        $App->Session->remove('is_auth');
    }

    // Check if already logged in
    if ($App->Session->has('is_auth')) {
        $Response = new RedirectResponse('experiments.php');
        $Response->send();
        exit;
    }

    // don't show the local login form if it's disabled
    $showLocal = true;
    // if there is a ?letmein in the url, we still show it.
    if (($App->Config->configArr['local_login'] === '0' && !$App->Request->query->has('letmein')) || $App->Config->configArr['local_auth_enabled'] === '0') {
        $showLocal = false;
    }

    $idpsArr = array();
    // only make the query to fetch idp list if we actually have enabled saml
    if ($App->Config->configArr['saml_toggle'] === '1') {
        $Idps = new Idps($App->Users);
        $idpsArr = $Idps->readAllSimpleEnabled();
    }

    if ($App->Request->cookies->has('kickreason')) {
        // at the moment there is only one reason
        $App->Session->getFlashBag()->add('ko', _('Your session expired.'));
    }

    $template = 'login-base.html';
    $renderArr = array(
        'idpsArr' => $idpsArr,
        'pageTitle' => _('Login'),
        'teamsArr' => $App->Teams->readAllVisible(),
        'showLocal' => $showLocal,
        'hideTitle' => true,
    );
    $Response->setContent($App->render($template, $renderArr));
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
