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

/**
 * User Control Panel
 *
 */
try {
    require_once 'app/init.inc.php';
    $pageTitle = _('User Control Panel');
    $selectedMenu = null;
    require_once('app/head.inc.php');

    $TeamGroups = new TeamGroups($Users->userData['team']);
    $teamGroupsArr = $TeamGroups->readAll();

    $Templates = new Templates($Users->userData['team']);
    $templatesArr = $Templates->readFromUserid($Users->userid);

    echo $twig->render('ucp.html', array(
        'Users' => $Users,
        'langsArr' => Tools::getLangsArr(),
        'teamsGroupsArr' => $teamsGroupsArr,
        'templatesArr' => $templatesArr
    ));

} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
