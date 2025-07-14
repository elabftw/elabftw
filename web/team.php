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

use Elabftw\Exceptions\AppException;
use Elabftw\Models\ProcurementRequests;
use Elabftw\Models\TeamGroups;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * The TEAM page
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    $Response->prepare($App->Request);
    $TeamGroups = new TeamGroups($App->Users);
    $ProcurementRequests = new ProcurementRequests($App->Teams);

    $template = 'team.html';
    $renderArr = array(
        'pageTitle' => _('Team'),
        'teamGroupsArr' => $TeamGroups->readAll(),
        'teamProcurementRequestsArr' => $ProcurementRequests->readAll(),
        'teamsStats' => $App->Teams->getStats($App->Users->userData['team']),
    );

    $Response->setContent($App->render($template, $renderArr));
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
