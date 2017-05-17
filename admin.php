<?php
/**
 * admin.php
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
 * Administration panel of a team
 *
 */
try {
//header("Content-Security-Policy: default-src 'none'; script-src 'self' 'unsafe-eval' https://www.google.com/; connect-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://ajax.googleapis.com/ https://www.google.com/; font-src 'self'; object-src 'self';");

    require_once 'app/init.inc.php';
    $pageTitle = _('Admin panel');
    require_once 'app/head.inc.php';

    if (!$_SESSION['is_admin']) {
        throw new Exception(Tools::error(true));
    }

    $Auth = new Auth();
    $Config = new Config();
    $FormKey = new FormKey();

    $Users = new Users($_SESSION['userid'], $Config);
    $ItemsTypes = new ItemsTypes($Users);
    $Status = new Status($Users);
    $TeamGroups = new TeamGroups($_SESSION['team_id']);
    $Templates = new Templates($Users);
    $Teams = new Teams($_SESSION['team_id']);

    $itemsTypesArr = $ItemsTypes->readAll();
    $statusArr = $Status->readAll();
    $teamConfigArr = $Teams->read();
    $teamGroupsArr = $TeamGroups->readAll();
    $templatesArr = $Templates->readCommon();
    $unvalidatedUsersArr = $Users->readAllFromTeam($_SESSION['team_id'], 0);
    $usersArr = $Users->readAllFromTeam($_SESSION['team_id']);

    echo $twig->render('admin.html', array(
        'Auth' => $Auth,
        'Config' => $Config,
        'FormKey' => $FormKey,
        'itemsTypesArr' => $itemsTypesArr,
        'statusArr' => $statusArr,
        'session' => $_SESSION,
        'teamConfigArr' => $teamConfigArr,
        'teamGroupsArr' => $teamGroupsArr,
        'templatesArr' => $templatesArr,
        'Users' => $Users,
        'unvalidatedUsersArr' => $unvalidatedUsersArr,
        'usersArr' => $usersArr
    ));
} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
