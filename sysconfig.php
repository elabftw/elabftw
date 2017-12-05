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
use Symfony\Component\HttpFoundation\Response;

/**
 * Administrate elabftw install
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('eLabFTW Configuration');

try {
    if ($Session->get('is_sysadmin') != 1) {
        throw new Exception(Tools::error(true));
    }

    $Idps = new Idps();
    $idpsArr = $Idps->readAll();
    $logsArr = $App->Logs->readAll();
    $TeamsView = new TeamsView(new Teams($App->Users));
    $teamsArr = $TeamsView->Teams->readAll();
    $usersArr = $App->Users->readAll();
    $ReleaseCheck = new ReleaseCheck($App->Config);
    $langsArr = Tools::getLangsArr();

    switch ($App->Config->configArr['mail_method']) {
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

    $template = 'sysconfig.html';
    $renderArr = array(
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
        'teamsArr' => $teamsArr,
        'usersArr' => $usersArr
    );

} catch (Exception $e) {
    $App->Logs->create('Error', $Session->get('userid'), $e->getMessage());
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());

} finally {
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
