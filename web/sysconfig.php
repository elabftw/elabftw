<?php
/**
 * sysconfig.php
 *
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ReleaseCheckException;
use Elabftw\Models\Idps;
use Elabftw\Models\Teams;
use Elabftw\Services\UsersHelper;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Administrate elabftw install
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('eLabFTW Configuration');
$Response = new Response();
$Response->prepare($Request);

$template = 'error.html';
$renderArr = array();

try {
    if (!$App->Session->get('is_sysadmin')) {
        throw new IllegalActionException('Non sysadmin user tried to access sysconfig panel.');
    }

    $Idps = new Idps();
    $idpsArr = $Idps->readAll();
    $Teams = new Teams($App->Users);
    $teamsArr = $Teams->readAll();
    $teamsStats = $Teams->getAllStats();

    // Users search
    $isSearching = false;
    $usersArr = array();
    if ($Request->query->has('q')) {
        $isSearching = true;
        $usersArr = $App->Users->readFromQuery(filter_var($Request->query->get('q'), FILTER_SANITIZE_STRING));
        foreach ($usersArr as &$user) {
            $UsersHelper = new UsersHelper((int) $user['userid']);
            $user['teams'] = $UsersHelper->getTeamsFromUserid();
        }
    }

    $ReleaseCheck = new ReleaseCheck($App->Config);
    try {
        $ReleaseCheck->getUpdatesIni();
    } catch (ReleaseCheckException $e) {
        $App->Log->warning('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
    }

    $langsArr = Tools::getLangsArr();

    $phpInfos = array(
        PHP_OS,
        PHP_VERSION,
        PHP_INT_MAX,
        PHP_SYSCONFDIR,
        ini_get('upload_max_filesize'),
        ini_get('date.timezone'),
    );

    $elabimgVersion = getenv('ELABIMG_VERSION') ?: 'Not in Docker';

    $privacyPolicyTemplate = \file_get_contents(\dirname(__DIR__) . '/src/templates/privacy-policy.html');

    $template = 'sysconfig.html';
    $renderArr = array(
        'Teams' => $Teams,
        'elabimgVersion' => $elabimgVersion,
        'ReleaseCheck' => $ReleaseCheck,
        'langsArr' => $langsArr,
        'fromSysconfig' => true,
        'idpsArr' => $idpsArr,
        'isSearching' => $isSearching,
        'phpInfos' => $phpInfos,
        'privacyPolicyTemplate' => $privacyPolicyTemplate,
        'teamsArr' => $teamsArr,
        'teamsStats' => $teamsStats,
        'usersArr' => $usersArr,
    );
} catch (IllegalActionException $e) {
    $renderArr['error'] = Tools::error(true);
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $renderArr['error'] = $e->getMessage();
} finally {
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
