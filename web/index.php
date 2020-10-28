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

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Idps;
use Elabftw\Services\LoginHelper;
use Elabftw\Services\SamlAuth;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

require_once 'app/init.inc.php';

$location = '../../experiments.php';
$Response = new RedirectResponse($location);

try {
    // SAML: IDP will redirect to this page after user login on IDP website
    if ($App->Request->query->has('acs')) {
        $AuthService = new SamlAuth($App->Config, new Idps());
        $AuthResponse = $AuthService->assertIdpResponse();

        $LoginHelper = new LoginHelper($AuthResponse, $App->Session);
        $LoginHelper->login(false);

        $location = $App->Request->cookies->get('redirect') ?? $location;

        // if the user is in several teams, we need to redirect to the team selection
        if ($AuthResponse->selectedTeam === null) {
            $App->Session->set('team_selection_required', true);
            $App->Session->set('team_selection', $AuthResponse->selectableTeams);
            $App->Session->set('auth_userid', $AuthResponse->userid);
            $location = '../../login.php';
        }
    }

    $Response = new RedirectResponse($location);
} catch (ImproperActionException $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array('Exception' => $e));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
} finally {
    $Response->send();
}
