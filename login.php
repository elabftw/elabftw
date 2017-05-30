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

/**
 * Login page
 *
 */
try {
    require_once 'app/init.inc.php';
    $pageTitle = _('Login');
    // Check if already logged in
    if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1) {
        header('Location: experiments.php');
        throw new Exception('Already logged in');
    }

    require_once 'app/head.inc.php';

    $Config = new Config();
    $Idps = new Idps();
    $FormKey = new FormKey();
    $BannedUsers = new BannedUsers($Config);

    // if we are not in https, die saying we work only in https
    if (!Tools::usingSsl()) {
        // get the url to display a link to click (without the port)
        $url = 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
        $message = "eLabFTW works only in HTTPS. Please enable HTTPS on your server. Or click this link : <a href='" .
            $url . "'>$url</a>";
        throw new Exception($message);
    }

    // Check if we are banned after too much failed login attempts
    if (in_array(md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']), $BannedUsers->readAll())) {
        throw new Exception(_('You cannot login now because of too many failed login attempts.'));
    }

    // show message if there is a failed_attempt
    if (isset($_SESSION['failed_attempt']) && $_SESSION['failed_attempt'] < $Config->configArr['login_tries']) {
        $number_of_tries_left = $Config->configArr['login_tries'] - $_SESSION['failed_attempt'];
        $message = _('Number of login attempt left before being banned for') . ' ' .
            $Config->configArr['ban_time'] . ' ' . _('minutes:') . ' ' . $number_of_tries_left;
        echo Tools::displayMessage($message, 'ko');
    }

    // disable login if too much failed_attempts
    if (isset($_SESSION['failed_attempt']) && $_SESSION['failed_attempt'] >= $Config->configArr['login_tries']) {
        // get user infos
        $fingerprint = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        // add the user to the banned list
        $BannedUsers->create($fingerprint);

        unset($_SESSION['failed_attempt']);
        throw new Exception(_('You cannot login now because of too many failed login attempts.'));
    }

    // don't show the local login form if it's disabled
    $showLocal = true;
    // if there is a ?letmein in the url, we still show it.
    if (!$Config->configArr['local_login'] && !isset($_GET['letmein'])) {
        $showLocal = false;
    }

    $idpsArr = $Idps->readAll();

    echo $twig->render('login.html', array(
        'BannedUsers' => $BannedUsers,
        'Config' => $Config,
        'FormKey' => $FormKey,
        'SESSION' => $_SESSION,
        'idpsArr' => $idpsArr,
        'showLocal' => $showLocal
    ));

} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
