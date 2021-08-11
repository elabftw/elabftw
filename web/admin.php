<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Services\UsersHelper;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Administration panel of a team
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Admin panel'); // @phan-suppress PhanTypeExepectedObjectPropAccessButGotNull
$Response = new Response();
$Response->prepare($Request);

$template = 'error.html';
$renderArr = array();

try {
    if (!$App->Session->get('is_admin')) {
        throw new IllegalActionException('Non admin user tried to access admin controller.');
    }

    $ItemsTypes = new ItemsTypes($App->Users);
    $Status = new Status($App->Users->team);
    $Tags = new Tags(new Experiments($App->Users));
    $TeamGroups = new TeamGroups($App->Users);
    $Teams = new Teams($App->Users);

    $itemsTypesArr = $ItemsTypes->readAll();
    $statusArr = $Status->read(new ContentParams());
    $teamConfigArr = $Teams->read(new ContentParams());
    $teamGroupsArr = $TeamGroups->read(new ContentParams());
    $teamsArr = $Teams->readAll();
    $allTeamUsersArr = $App->Users->readAllFromTeam();
    // only the unvalidated ones
    $unvalidatedUsersArr = array_filter($allTeamUsersArr, function ($u) {
        return $u['validated'] === '0';
    });
    // Users search
    $isSearching = false;
    $usersArr = array();
    if ($Request->query->has('q')) {
        $isSearching = true;
        $usersArr = $App->Users->readFromQuery(filter_var($Request->query->get('q'), FILTER_SANITIZE_STRING), true);
        foreach ($usersArr as &$user) {
            $UsersHelper = new UsersHelper((int) $user['userid']);
            $user['teams'] = $UsersHelper->getTeamsFromUserid();
        }
    }


    // all the tags for the team
    $tagsArr = $Tags->readAll();

    $template = 'admin.html';
    $renderArr = array(
        'allTeamUsersArr' => $allTeamUsersArr,
        'tagsArr' => $tagsArr,
        'fromSysconfig' => false,
        'isSearching' => $isSearching,
        'itemsTypesArr' => $itemsTypesArr,
        'statusArr' => $statusArr,
        'teamConfigArr' => $teamConfigArr,
        'teamGroupsArr' => $teamGroupsArr,
        'visibilityArr' => $TeamGroups->getVisibilityList(),
        'teamsArr' => $teamsArr,
        'unvalidatedUsersArr' => $unvalidatedUsersArr,
        'usersArr' => $usersArr,
    );
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $renderArr['error'] = Tools::error(true);
} catch (DatabaseErrorException | FilesystemErrorException | ImproperActionException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $renderArr['error'] = $e->getMessage();
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $renderArr['error'] = Tools::error();
} finally {
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
