<?php
/**
 * ucp.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * User Control Panel
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('User Control Panel');

try {
    $TeamGroups = new TeamGroups($Users);
    $teamGroupsArr = $TeamGroups->readAll();

    $Templates = new Templates($Users);
    $templatesArr = $Templates->readFromUserid();

    $template = 'ucp.html';
    $renderArr = array(
        'Users' => $Users,
        'langsArr' => Tools::getLangsArr(),
        'teamGroupsArr' => $teamGroupsArr,
        'templatesArr' => $templatesArr
    );

} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
}

echo $App->render($template, $renderArr);
