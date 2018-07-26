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
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Administrate elabftw install
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('eLabFTW Configuration');
$Response = new Response();
$Response->prepare($Request);

try {
    if ($Session->get('is_sysadmin') != 1) {
        throw new Exception(Tools::error(true));
    }

    $Idps = new Idps();
    $idpsArr = $Idps->readAll();
    $TeamsView = new TeamsView(new Teams($App->Users));
    $teamsArr = $TeamsView->Teams->readAll();
    $usersArr = $App->Users->readAll();
    $ReleaseCheck = new ReleaseCheck($App->Config);
    try {
        $ReleaseCheck->getUpdatesIni();
    } catch (RuntimeException $e) {
        $App->Log->warning('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
    }

    $langsArr = Tools::getLangsArr();

    $phpInfos = array(
        PHP_OS,
        PHP_VERSION,
        PHP_INT_MAX,
        PHP_SYSCONFDIR,
        ini_get('upload_max_filesize'),
        ini_get('date.timezone')
    );

    $template = 'sysconfig.html';
    $renderArr = array(
        'ReleaseCheck' => $ReleaseCheck,
        'TeamsView' => $TeamsView,
        'langsArr' => $langsArr,
        'fromSysconfig' => true,
        'idpsArr' => $idpsArr,
        'phpInfos' => $phpInfos,
        'teamsArr' => $teamsArr,
        'usersArr' => $usersArr
    );

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
}

$Response->setContent($App->render($template, $renderArr));
$Response->send();
