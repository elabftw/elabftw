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
require_once 'app/init.inc.php';
$App->pageTitle = _('Admin panel');

try {
    if (!$Session->get('is_admin')) {
        throw new Exception(Tools::error(true));
    }

    $FormKey = new FormKey($Session);

    $ItemsTypes = new ItemsTypes($Users);
    $Status = new Status($Users);
    $TeamGroups = new TeamGroups($Users);
    $Templates = new Templates($Users);

    $itemsTypesArr = $ItemsTypes->readAll();
    $statusArr = $Status->readAll();
    $teamGroupsArr = $TeamGroups->readAll();
    $commonTplBody = $Templates->readCommonBody();
    // only the unvalidated ones
    $unvalidatedUsersArr = $Users->readAllFromTeam(0);
    // all users
    $usersArr = $Users->readAllFromTeam();

    $template = 'admin.html';
    $renderArr = array(
        'Auth' => $Auth,
        'FormKey' => $FormKey,
        'fromSysconfig' => false,
        'itemsTypesArr' => $itemsTypesArr,
        'statusArr' => $statusArr,
        'teamGroupsArr' => $teamGroupsArr,
        'commonTplBody' => $commonTplBody,
        'Users' => $Users,
        'unvalidatedUsersArr' => $unvalidatedUsersArr,
        'usersArr' => $usersArr
    );

} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
}

echo $App->render($template, $renderArr);
