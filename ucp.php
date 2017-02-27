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
try {
    require_once 'app/init.inc.php';
    $pageTitle = _('User Control Panel');
    $selectedMenu = null;
    require_once 'app/head.inc.php';

    $TeamGroups = new TeamGroups($Users->userData['team']);
    $teamGroupsArr = $TeamGroups->readAll();

    $Templates = new Templates($Users);
    $templatesArr = $Templates->readFromUserid();

    echo $twig->render('ucp.html', array(
        'Users' => $Users,
        'langsArr' => Tools::getLangsArr(),
        'teamGroupsArr' => $teamGroupsArr,
        'templatesArr' => $templatesArr
    ));

} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
