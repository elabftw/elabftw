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
use Elabftw\Exceptions\AppException;
use Elabftw\Make\Exports;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\UserUploads;
use Elabftw\Services\UsersHelper;
use Exception;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Display profile of current user
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    $Response->prepare($App->Request);
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
    $ExperimentsCategories = new ExperimentsCategories($App->Teams);

    // get the exported files
    $Export = new Exports($App->Users, Storage::EXPORTS->getStorage());

    $UserUploads = new UserUploads($App->Users);
    $PermissionsHelper = new PermissionsHelper();

    $queryParams = $UserUploads->getQueryParams(new InputBag($App->Request->query->all()));
    $template = 'profile.html';
    $renderArr = array(
        'attachedFiles' => $UserUploads->readAll($queryParams),
        'count' => $count,
        'exportedFiles' => $Export->readAll(),
        'experimentsCategoryArr' => $ExperimentsCategories->readAll(),
        'maxUploadSizeRaw' => ini_get('post_max_size'),
        'pageTitle' => _('Profile'),
        'pieData' => $UserStats->getPieData(),
        'pieDataCss' => $UserStats->getFormattedPieData(),
        'teamGroupsArr' => $teamGroupsArr,
        'teamsArr' => $teams,
        'uploadsTotal' => $UserUploads->countAll($queryParams),
        'usersArr' => $App->Users->readAllActiveFromTeam(),
        'visibilityArr' => $PermissionsHelper->getAssociativeArray(),
    );
    $Response->setContent($App->render($template, $renderArr));
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
