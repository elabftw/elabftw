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

use Elabftw\Models\TeamGroups;
use Elabftw\Services\UsersHelper;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Display profile of current user
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Profile');

/** @psalm-suppress UncaughtThrowInGlobalScope */
$Response = new Response();
$Response->prepare($App->Request);

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

    $template = 'profile.html';
    $renderArr = array(
        'count' => $count,
        'pieData' => $UserStats->getPieData(),
        'pieDataCss' => $UserStats->getFormattedPieData(),
        'teamGroupsArr' => $teamGroupsArr,
        'teamsArr' => $teams,
    );
} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
}

$Response->setContent($App->render($template, $renderArr));
$Response->send();
