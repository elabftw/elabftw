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
use Symfony\Component\HttpFoundation\Response;

/**
 * The team page
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Team');

try {

    if ($App->Session->has('anon')) {
        throw new Exception(Tools::error(true));
    }

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

    $Templates = new Templates($App->Users);
    $templatesArr = $Templates->readFromTeam();

    $template = 'team.html';
    $renderArr = array(
        'TeamsView' => $TeamsView,
        'Scheduler' => $Scheduler,
        'itemsArr' => $itemsArr,
        'selectedItem' => $selectedItem,
        'templatesArr' => $templatesArr,
        'lang' => Tools::getCalendarLang($App->Users->userData['lang'])
    );

} catch (Exception $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());

} finally {
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
