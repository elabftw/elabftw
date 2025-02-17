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

use Elabftw\Enums\Storage;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Make\Exports;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\UserUploads;
use Elabftw\Services\UsersHelper;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Display profile of current user
 */
require_once 'app/init.inc.php';

/** @psalm-suppress UncaughtThrowInGlobalScope */
$Response = new Response();
$Response->prepare($App->Request);

$template = 'profile.html';
try {
    $UsersHelper = new UsersHelper($App->Users->userData['userid']);
    // get total number of experiments
    $count = $UsersHelper->countExperiments();

    // generate stats for the pie chart with experiments status
    $UserStats = new UserStats($App->Users, $count);

    // get the teams
    $teams = $UsersHelper->getTeamsFromUserid();

    // get the team groups in which the user is
    $TeamGroups = new TeamGroups($App->Users);
    $teamGroupsArr = $TeamGroups->readGroupsWithUsersFromUser();
    $ExperimentsCategories = new ExperimentsCategories(new Teams($App->Users));

    // get the exported files
    $Export = new Exports($App->Users, Storage::EXPORTS->getStorage());

    $UserUploads = new UserUploads($App->Users);
    $PermissionsHelper = new PermissionsHelper();

    $renderArr = array(
        'attachedFiles' => $UserUploads->readAll(),
        'count' => $count,
        'exportedFiles' => $Export->readAll(),
        'experimentsCategoryArr' => $ExperimentsCategories->readAll(),
        'maxUploadSizeRaw' => ini_get('post_max_size'),
        'pageTitle' => _('Profile'),
        'pieData' => $UserStats->getPieData(),
        'pieDataCss' => $UserStats->getFormattedPieData(),
        'teamGroupsArr' => $teamGroupsArr,
        'teamsArr' => $teams,
        'uploadsTotal' => $UserUploads->countAll(),
        'usersArr' => $App->Users->readAllActiveFromTeam(),
        'visibilityArr' => $PermissionsHelper->getAssociativeArray(),
    );
    $Response->setContent($App->render($template, $renderArr));
} catch (ImproperActionException $e) {
    // show message to user
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (IllegalActionException $e) {
    $template = 'error.html';
    // log notice and show message
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $renderArr = array('error' => Tools::error(true));
    $Response->setContent($App->render($template, $renderArr));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    // log error and show message
    $template = 'error.html';
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (Exception $e) {
    // log error and show general error message
    $template = 'error.html';
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $renderArr = array('error' => Tools::error());
    $Response->setContent($App->render($template, $renderArr));
} finally {
    $Response->send();
}
