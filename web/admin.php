<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

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
use Elabftw\Models\Templates;
use Elabftw\Services\UsersHelper;
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
    if (!$App->Session->get('is_admin')) {
        throw new IllegalActionException('Non admin user tried to access admin controller.');
    }

    $ItemsTypes = new ItemsTypes($App->Users);
    $Status = new Status($App->Users);
    $Tags = new Tags(new Experiments($App->Users));
    $TeamGroups = new TeamGroups($App->Users);
    $Teams = new Teams($App->Users);
    $Templates = new Templates($App->Users);
    $UsersHelper = new UsersHelper();

    $itemsTypesArr = $ItemsTypes->readAll();
    $statusArr = $Status->readAll();
    $teamGroupsArr = $TeamGroups->readAll();
    $visibilityArr = $TeamGroups->getVisibilityList();
    $teamsArr = $Teams->readAll();
    $commonTplBody = $Templates->readCommonBody();
    // only the unvalidated ones
    $unvalidatedUsersArr = $App->Users->readAllFromTeam(0);
    // Users search
    $isSearching = false;
    $usersArr = array();
    if ($Request->query->has('q')) {
        $isSearching = true;
        $usersArr = $App->Users->readFromQuery(filter_var($Request->query->get('q'), FILTER_SANITIZE_STRING), true);
    }

    $allTeamUsersArr = $App->Users->readAllFromTeam(1);

    // all the tags for the team
    $tagsArr = $Tags->readAll();

    $template = 'admin.html';
    $renderArr = array(
        'UsersHelper' => $UsersHelper,
        'allTeamUsersArr' => $allTeamUsersArr,
        'tagsArr' => $tagsArr,
        'fromSysconfig' => false,
        'isSearching' => $isSearching,
        'itemsTypesArr' => $itemsTypesArr,
        'statusArr' => $statusArr,
        'teamGroupsArr' => $teamGroupsArr,
        'visibilityArr' => $visibilityArr,
        'teamsArr' => $teamsArr,
        'commonTplBody' => $commonTplBody,
        'unvalidatedUsersArr' => $unvalidatedUsersArr,
        'usersArr' => $usersArr,
    );
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error(true));
} catch (DatabaseErrorException | FilesystemErrorException | ImproperActionException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
} finally {
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
