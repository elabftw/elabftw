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

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Login page
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Login');

try {
    // Check if already logged in
    if ($Session->has('auth') || $Session->has('anon')) {
        $Response = new RedirectResponse("experiments.php");
        $Response->send();
        exit;
    }

    $FormKey = new FormKey($Session);
    $BannedUsers = new BannedUsers($App->Config);

    // if we are not in https, die saying we work only in https
    if (!$Request->isSecure() && !$Request->server->has('HTTP_X_FORWARDED_PROTO')) {
        // get the url to display a link to click (without the port)
        $url = Tools::getUrl($Request);
        $message = "eLabFTW works only in HTTPS. Please enable HTTPS on your server. Or click this link : <a href='" .
            $url . "'>$url</a>";
        throw new Exception($message);
    }

    // disable login if too much failed_attempts
    if ($Session->has('failed_attempt') && $Session->get('failed_attempt') >= $App->Config->configArr['login_tries']) {
        // get user infos
        $fingerprint = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        // add the user to the banned list
        $BannedUsers->create($fingerprint);

        $Session->remove('failed_attempt');
    }

    // Check if we are banned after too much failed login attempts
    if (in_array(md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']), $BannedUsers->readAll(), true)) {
        throw new Exception(_('You cannot login now because of too many failed login attempts.'));
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
        'FormKey' => $FormKey,
        'Session' => $Session,
        'idpsArr' => $idpsArr,
        'teamsArr' => $teamsArr,
        'showLocal' => $showLocal
    );


} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
} finally {
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
