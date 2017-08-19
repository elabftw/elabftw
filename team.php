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

    $TeamsView = new TeamsView(new Teams($Users->userData['team']));
    $Database = new Database($Users);
    // we only want the bookable type of items
    $Database->bookableFilter = " AND bookable = 1";
    $itemsArr = $Database->read();
    $Scheduler = new Scheduler($Database);

    $selectedItem = null;
    if ($Request->query->get('item')) {
        $Scheduler->Database->setId($Request->query->get('item'));
        $selectedItem = ($Request->query->get('item'));

        $Scheduler->populate();
        if (strlen($Scheduler->itemData['category']) === 0) {
            throw new Exception(_('Nothing to show with this id'));
        }
    }

    echo $Twig->render('team.html', array(
        'Users' => $Users,
        'TeamsView' => $TeamsView,
        'Scheduler' => $Scheduler,
        'itemsArr' => $itemsArr,
        'selectedItem' => $selectedItem,
        'lang' => Tools::getCalendarLang($Users->userData['lang'])
    ));

} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
