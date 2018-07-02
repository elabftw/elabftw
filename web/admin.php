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
use Symfony\Component\HttpFoundation\Response;

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

    $ItemsTypes = new ItemsTypes($App->Users);
    $Status = new Status($App->Users);
    $Tags = new Tags(new Experiments($App->Users));
    $TeamGroups = new TeamGroups($App->Users);
    $Templates = new Templates($App->Users);

    $itemsTypesArr = $ItemsTypes->readAll();
    $statusArr = $Status->readAll();
    $teamGroupsArr = $TeamGroups->readAll();
    $commonTplBody = $Templates->readCommonBody();
    // only the unvalidated ones
    $unvalidatedUsersArr = $App->Users->readAllFromTeam(0);
    // all users
    $usersArr = $App->Users->readAllFromTeam();

    // all the tags for the team
    $tagsArr = $Tags->readAll();

    $template = 'admin.html';
    $renderArr = array(
        'tagsArr' => $tagsArr,
        'FormKey' => $FormKey,
        'fromSysconfig' => false,
        'itemsTypesArr' => $itemsTypesArr,
        'statusArr' => $statusArr,
        'teamGroupsArr' => $teamGroupsArr,
        'commonTplBody' => $commonTplBody,
        'unvalidatedUsersArr' => $unvalidatedUsersArr,
        'usersArr' => $usersArr
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
