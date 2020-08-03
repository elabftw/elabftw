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
use Exception;
use function implode;
use function in_array;
use function md5;
use function str_split;
use Symfony\Component\HttpFoundation\Response;

/**
 * 2FA page
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Two Factor Authentication');

$Response = new Response();
$Response->prepare($Request);

try {
    $BannedUsers = new BannedUsers($App->Config);

    // disable login if too much failed_attempts
    if ($Session->has('failed_attempt') && $Session->get('failed_attempt') >= $App->Config->configArr['login_tries']) {
        // get user info
        $fingerprint = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        // add the user to the banned list
        $BannedUsers->create($fingerprint);

        $Session->remove('failed_attempt');
    }

    // Check if we are banned after too much failed login attempts
    if (in_array(md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']), $BannedUsers->readAll(), true)) {
        throw new ImproperActionException(_('You cannot login now because of too many failed login attempts.'));
    }

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
