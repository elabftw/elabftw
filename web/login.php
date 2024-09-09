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

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Idps;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Services\MfaHelper;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use function implode;
use function str_split;

/**
 * Login page
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Login');

$Response = new Response();
$Response->prepare($App->Request);

try {
    // if we are not in https, die saying we work only in https
    if (!$App->Request->isSecure() && !$App->Request->server->has('HTTP_X_FORWARDED_PROTO')) {
        // get the url to display a link to click
        $url = Config::fromEnv('SITE_URL');
        $message = "eLabFTW works only in HTTPS. Please enable HTTPS on your server or ensure X-Forwarded-Proto header is correctly sent by the load balancer. Or follow this link : <a href='" .
            $url . "'>$url</a>";
        throw new ImproperActionException($message);
    }

    if ($App->Request->query->has('rm_teaminit')) {
        $App->Session->remove('teaminit_done');
    }

    // Show MFA if necessary
    if ($App->Session->has('mfa_auth_required')) {
        $App->pageTitle = _('Two Factor Authentication');
        $template = 'mfa.html';
        $renderArr = array('hideTitle' => true);

        // If one enables 2FA we need to provide the secret.
        // For user convenience it is provide as QR code and as plain text.
        if ($App->Session->has('enable_mfa')) {
            // User is not fully authenticated, we load the user as we need email
            if ($App->Session->get('enforce_mfa')) {
                $App->Users = new Users($App->Session->get('auth_userid'));
            }
            $MfaHelper = new MfaHelper($App->Users->userData['userid'], $App->Session->get('mfa_secret'));
            $renderArr['mfaQRCodeImageDataUri'] = $MfaHelper->getQRCodeImageAsDataUri($App->Users->userData['email']);
            $renderArr['mfaSecret'] = implode(' ', str_split($App->Session->get('mfa_secret'), 4));
        }
        $Response->setContent($App->render($template, $renderArr));
        $Response->send();
        exit;
    }

    if ($App->Request->query->get('switch_team') === '1') {
        $App->Session->set('team_switch_required', true);
        $App->Session->set('team_selection', $App->Users->userData['teams']);
        $App->Session->set('auth_userid', $App->Users->userData['userid']);
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

    $Idps = new Idps($App->Users);
    $idpsArr = $Idps->readAllSimpleEnabled();

    $Teams = new Teams($App->Users);
    $Teams->bypassReadPermission = true;
    $teamsArr = $Teams->readAll();

    if ($App->Request->cookies->has('kickreason')) {
        // at the moment there is only one reason
        $App->ko[] = _('Your session expired.');
    }

    $template = 'login.html';
    $renderArr = array(
        'idpsArr' => $idpsArr,
        'teamsArr' => $teamsArr,
        'showLocal' => $showLocal,
        'hideTitle' => true,
    );
    $Response->setContent($App->render($template, $renderArr));
} catch (ImproperActionException $e) {
    // show message to user
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (IllegalActionException $e) {
    // log notice and show message
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error(true));
    $Response->setContent($App->render($template, $renderArr));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    // log error and show message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
    $Response->setContent($App->render($template, $renderArr));
} finally {
    $Response->send();
}
