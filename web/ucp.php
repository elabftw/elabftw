<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Models\ApiKeys;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Templates;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * User Control Panel
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('User Control Panel');

$Response = new Response();
$Response->prepare($Request);

try {
    $ApiKeys = new ApiKeys($App->Users);
    $apiKeysArr = $ApiKeys->readAll();

    $TeamGroups = new TeamGroups($App->Users);
    $teamGroupsArr = $TeamGroups->readAll();

    $Templates = new Templates($App->Users);
    $templatesArr = $Templates->readFromTeam();

    $filterTemplates = [];
    $i=0;
    while($i < sizeof($templatesArr)){
        $templateSteps = $Templates->readTemplateSteps($templatesArr[$i]['id']);
        if($App->Users->userData['show_team_template'] == 1){
            $filterTemplates[$i] = $templatesArr[$i];
            $filterTemplates[$i]['steps'] = $templateSteps[0]['steps'];
        } else if($templatesArr[$i]['userid'] == $App->Users->userData['userid']
                    || $templatesArr[$i]['userid'] == 0){
            $filterTemplates[$i]= $templatesArr[$i];
            $filterTemplates[$i]['steps'] = $templateSteps[0]['steps'];
        }
        $i++;
    }

    // TEAM GROUPS
    // Added Visibility clause
    $TeamGroups = new TeamGroups($App->Users);
    $visibilityArr = $TeamGroups->getVisibilityList();

    $template = 'ucp.html';
    $renderArr = array(
        'Entity' => $Templates,
        'apiKeysArr' => $apiKeysArr,
        'langsArr' => Tools::getLangsArr(),
        'teamGroupsArr' => $teamGroupsArr,
        'templatesArr' => $filterTemplates,
        'visibilityArr' => $visibilityArr, // Added Visibility

    );
} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
}
$Response->setContent($App->render($template, $renderArr));
$Response->send();
