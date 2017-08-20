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

    require_once 'app/init.inc.php';
    $pageTitle = _('Admin panel');
    require_once 'app/head.inc.php';

    if (!$Session->get('is_admin')) {
        throw new Exception(Tools::error(true));
    }

    $Auth = new Auth();
    $FormKey = new FormKey($Session);

    $ItemsTypes = new ItemsTypes($Users);
    $Status = new Status($Users);
    $TeamGroups = new TeamGroups($Users);
    $Templates = new Templates($Users);
    $Teams = new Teams($Session->get('team'));

    $itemsTypesArr = $ItemsTypes->readAll();
    $statusArr = $Status->readAll();
    $teamConfigArr = $Teams->read();
    $teamGroupsArr = $TeamGroups->readAll();
    $commonTplBody = $Templates->readCommonBody();
    $unvalidatedUsersArr = $Users->readAllFromTeam($Session->get('team'), 0);
    $usersArr = $Users->readAllFromTeam($Session->get('team'));

    echo $Twig->render('admin.html', array(
        'Auth' => $Auth,
        'Config' => $Config,
        'FormKey' => $FormKey,
        'fromSysconfig' => false,
        'itemsTypesArr' => $itemsTypesArr,
        'statusArr' => $statusArr,
        'Session' => $Session,
        'teamConfigArr' => $teamConfigArr,
        'teamGroupsArr' => $teamGroupsArr,
        'commonTplBody' => $commonTplBody,
        'Users' => $Users,
        'unvalidatedUsersArr' => $unvalidatedUsersArr,
        'usersArr' => $usersArr
    ));
} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
