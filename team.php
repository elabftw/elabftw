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
require_once 'app/init.inc.php';
$App->pageTitle = _('Team');

try {
    $TeamsView = new TeamsView(new Teams($App->Users));
    $Database = new Database($App->Users);
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

    $template = 'team.html';
    $renderArr = array(
        'TeamsView' => $TeamsView,
        'Scheduler' => $Scheduler,
        'itemsArr' => $itemsArr,
        'selectedItem' => $selectedItem,
        'lang' => Tools::getCalendarLang($App->Users->userData['lang'])
    );

} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
}

echo $App->render($template, $renderArr);
