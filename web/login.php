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
use Elabftw\Models\BannedUsers;
use Elabftw\Models\Idps;
use Elabftw\Models\Teams;
use Elabftw\Services\MfaHelper;
use Exception;
use function implode;
use function in_array;
use function md5;
use function str_split;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Login page
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Login');

$Response = new Response();
$Response->prepare($Request);

try {
    // if we are not in https, die saying we work only in https
    if (!$Request->isSecure() && !$Request->server->has('HTTP_X_FORWARDED_PROTO')) {
        // get the url to display a link to click (without the port)
        $url = Tools::getUrl($Request);
        $message = "eLabFTW works only in HTTPS. Please enable HTTPS on your server. Or click this link : <a href='" .
            $url . "'>$url</a>";
        throw new ImproperActionException($message);
    }

    // disable login if too much failed_attempts
    $BannedUsers = new BannedUsers($App->Config);
    // get user info
    $fingerprint = md5($App->Request->server->get('REMOTE_ADDR') . $App->Request->server->get('HTTP_USER_AGENT') ?? '');
    if ($App->Session->has('failed_attempt') && $App->Session->get('failed_attempt') >= $App->Config->configArr['login_tries']) {
        // add the user to the banned list
        $BannedUsers->create($fingerprint);

        $App->Session->remove('failed_attempt');
    }

    // Check if we are banned after too much failed login attempts
    if (in_array($fingerprint, $BannedUsers->readAll(), true)) {
        throw new ImproperActionException(_('You cannot login now because of too many failed login attempts.'));
    }

    // Show MFA if necessary
    if ($App->Session->has('mfa_auth_required')) {
        $App->pageTitle = _('Two Factor Authentication');
        $template = 'mfa.html';
        $renderArr = array('hideTitle' => true);

        // If one enables 2FA we need to provide the secret.
        // For user convenience it is provide as QR code and as plain text.
        if ($App->Session->has('enable_mfa')) {
            $MfaHelper = new MfaHelper((int) $App->Users->userData['userid'], $App->Session->get('mfa_secret'));
            $renderArr['mfaQRCodeImageDataUri'] = $MfaHelper->getQRCodeImageAsDataUri($App->Users->userData['email']);
            $renderArr['mfaSecret'] = implode(' ', str_split($App->Session->get('mfa_secret'), 4));
        }
        $Response->setContent($App->render($template, $renderArr));
        $Response->send();
        exit;
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
    if (!$App->Config->configArr['local_login'] && !$Request->query->has('letmein')) {
        $showLocal = false;
    }

    $Idps = new Idps();
    $idpsArr = $Idps->readAll();

    $Teams = new Teams($App->Users);
    $teamsArr = $Teams->readAll();

    $template = 'login.html';
    $renderArr = array(
        'BannedUsers' => $BannedUsers,
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
