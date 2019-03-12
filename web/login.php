<?php
/**
 * login.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\BannedUsers;
use Elabftw\Models\Idps;
use Elabftw\Models\Teams;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Login page
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Login');

$Response = new Response();
$Response->prepare($Request);

try {
    // Check if already logged in
    if ($Session->has('auth') || $Session->has('anon')) {
        $Response = new RedirectResponse("experiments.php");
        $Response->send();
        exit;
    }

    $BannedUsers = new BannedUsers($App->Config);

    // if we are not in https, die saying we work only in https
    if (!$Request->isSecure() && !$Request->server->has('HTTP_X_FORWARDED_PROTO')) {
        // get the url to display a link to click (without the port)
        $url = Tools::getUrl($Request);
        $message = "eLabFTW works only in HTTPS. Please enable HTTPS on your server. Or click this link : <a href='" .
            $url . "'>$url</a>";
        throw new ImproperActionException($message);
    }

    // disable login if too much failed_attempts
    if ($Session->has('failed_attempt') && $Session->get('failed_attempt') >= $App->Config->configArr['login_tries']) {
        // get user info
        $fingerprint = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        // add the user to the banned list
        $BannedUsers->create($fingerprint);

        $Session->remove('failed_attempt');
    }

    // Check if we are banned after too much failed login attempts
    if (\in_array(md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']), $BannedUsers->readAll(), true)) {
        throw new ImproperActionException(_('You cannot login now because of too many failed login attempts.'));
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
        'Session' => $Session,
        'idpsArr' => $idpsArr,
        'teamsArr' => $teamsArr,
        'showLocal' => $showLocal
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
