<?php
/**
 * team.php
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
 * The team page
 *
 */
try {
    require_once 'app/init.inc.php';
    $pageTitle = _('Team');
    $selectedMenu = 'Team';
    require_once 'app/head.inc.php';

    if (!isset($Users)) {
        $Users = new Users($_SESSION['userid']);
    }

    $TeamsView = new TeamsView(new Teams($_SESSION['team_id']));
    $Database = new Database($Users);
    // we only want the bookable type of items
    $Database->bookableFilter = " AND bookable = 1";
    $itemsArr = $Database->read();
    $Scheduler = new Scheduler($Database);
    if (isset($_GET['item']) && !empty($_GET['item'])) {
        $Scheduler->Database->setId($_GET['item']);
        $Scheduler->populate();
        if (strlen($Scheduler->itemData['category']) === 0) {
            throw new Exception(_('Nothing to show with this id'));
        }
    }

    echo $twig->render('team.html', array(
        'Users' => $Users,
        'TeamsView' => $TeamsView,
        'Scheduler' => $Scheduler,
        'itemsArr' => $itemsArr,
        'lang' => Tools::getCalendarLang($Users->userData['lang'])
    ));

} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once('app/footer.inc.php');
}
