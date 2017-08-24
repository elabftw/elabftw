<?php
/**
 * sysconfig.php
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
 * Administrate elabftw install
 *
 */

try {
    require_once 'app/init.inc.php';
    $pageTitle = _('eLabFTW Configuration');
    require_once 'app/head.inc.php';

    if ($Session->get('is_sysadmin') != 1) {
        throw new Exception(Tools::error(true));
    }

    $Auth = new Auth();
    $Idps = new Idps();
    $idpsArr = $Idps->readAll();
    $Logs = new Logs();
    $logsArr = $Logs->readAll();
    $TeamsView = new TeamsView(new Teams());
    $teamsArr = $TeamsView->Teams->readAll();
    $usersArr = $Users->readAll();
    $ReleaseCheck = new ReleaseCheck($Config);
    $langsArr = Tools::getLangsArr();

    switch ($Config->configArr['mail_method']) {
        case 'sendmail':
            $disable_sendmail = false;
            $disable_smtp = true;
            $disable_php = true;
            break;
        case 'smtp':
            $disable_sendmail = true;
            $disable_smtp = false;
            $disable_php = true;
            break;
        case 'php':
            $disable_sendmail = true;
            $disable_smtp = true;
            $disable_php = false;
            break;
        default:
            $disable_sendmail = true;
            $disable_smtp = true;
            $disable_php = true;
    }

    $phpInfos = array(PHP_OS, PHP_VERSION, PHP_INT_MAX, PHP_SYSCONFDIR);

    echo $Twig->render('sysconfig.html', array(
        'Auth' => $Auth,
        'Config' => $Config,
        'ReleaseCheck' => $ReleaseCheck,
        'TeamsView' => $TeamsView,
        'langsArr' => $langsArr,
        'disable_sendmail' => $disable_sendmail,
        'disable_smtp' => $disable_smtp,
        'disable_php' => $disable_php,
        'fromSysconfig' => true,
        'idpsArr' => $idpsArr,
        'phpInfos' => $phpInfos,
        'logsArr' => $logsArr,
        'Session' => $Session,
        'teamsArr' => $teamsArr,
        'usersArr' => $usersArr
    ));

} catch (Exception $e) {
    $Logs->create('Error', $Session->get('userid'), $e->getMessage());
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
