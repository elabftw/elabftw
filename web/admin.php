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
$Response = new Response();
$Response->prepare($Request);

try {
    if (!$Session->get('is_admin')) {
        throw new Exception(Tools::error(true));
    }

    $ItemsTypes = new ItemsTypes($App->Users);
    $Status = new Status($App->Users);
    $Tags = new Tags(new Experiments($App->Users));
    $TeamGroups = new TeamGroups($App->Users);
    $Teams = new Teams($App->Users);
    $Templates = new Templates($App->Users);

    $itemsTypesArr = $ItemsTypes->readAll();
    $statusArr = $Status->readAll();
    $teamGroupsArr = $TeamGroups->readAll();
    $teamsArr = $Teams->readAll();
    $commonTplBody = $Templates->readCommonBody();
    // only the unvalidated ones
    $unvalidatedUsersArr = $App->Users->readAllFromTeam(0);
    // Users search
    $isSearching = false;
    $usersArr = array();
    if ($Request->query->has('q')) {
        $isSearching = true;
        $usersArr = $App->Users->readTeamFromQuery(filter_var($Request->query->get('q'), FILTER_SANITIZE_STRING));
    }


    // all the tags for the team
    $tagsArr = $Tags->readAll();

    $template = 'admin.html';
    $renderArr = array(
        'tagsArr' => $tagsArr,
        'fromSysconfig' => false,
        'isSearching' => $isSearching,
        'itemsTypesArr' => $itemsTypesArr,
        'statusArr' => $statusArr,
        'teamGroupsArr' => $teamGroupsArr,
        'teamsArr' => $teamsArr,
        'commonTplBody' => $commonTplBody,
        'unvalidatedUsersArr' => $unvalidatedUsersArr,
        'usersArr' => $usersArr
    );

} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());

}

$Response->setContent($App->render($template, $renderArr));
$Response->send();
