<?php
/**
 * login.php
 *
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
    if ($App->Session->has('mfa_secret')) {
        $App->pageTitle = _('Two Factor Authentication');
        $template = 'mfa.html';
        $renderArr = array('hideTitle' => true);

        // If one enables 2FA we need to provide the secret.
        // For user convenience it is provide as QR code and as plain text.
        if ($App->Session->has('enable_mfa')) {
            $Mfa = new Mfa($App->Request, $App->Session);
            $renderArr['mfaQRCodeImageDataUri'] = $Mfa->getQRCodeImageAsDataUri($App->Users->userData['email']);
            $renderArr['mfaSecret'] = implode(' ', str_split($App->Session->get('mfa_secret'), 4));
        }
        $Response->setContent($App->render($template, $renderArr));
        $Response->send();
        exit();
    }

    // Check if already logged in
    if ($App->Session->has('auth') || $App->Session->has('anon')) {
        $Response = new RedirectResponse('experiments.php');
        $Response->send();
        exit;
    }

    // Check for external authentication by web server
    $remoteUser = $App->Request->server->get($App->Config->configArr['extauth_remote_user']);

    if (isset($remoteUser)) {
        $firstname = $App->Request->server->get($App->Config->configArr['extauth_firstname']);
        $lastname = $App->Request->server->get($App->Config->configArr['extauth_lastname']);
        $email = $App->Request->server->get($App->Config->configArr['extauth_email']);

        // try and get the team
        $teamId = $App->Request->server->get($App->Config->configArr['extauth_teams']);
        // no team found!
        if (empty($teamId)) {
            // check for the default team
            $teamId = (int) $App->Config->configArr['saml_team_default'];
            // or throw error if sysadmin configured it like that
            if ($teamId === 0) {
                throw new ImproperActionException('Could not find team ID to assign user!');
            }
        }
        $teams = array((string) $teamId);

        if (($userid = $Auth->getUseridFromEmail($email)) === 0) {
            $App->Users->create($email, $teams, $firstname, $lastname, '');
            $App->Log->info('New user ' . $email . ' autocreated from external auth');
            $userid = $Auth->getUseridFromEmail($email);
        }
        $Auth->login($userid);
        // add this to the session so for logout we know we need to hit the logout_url from config to logout from external server too
        $App->Session->set('is_ext_auth', 1);
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
