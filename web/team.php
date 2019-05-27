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
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Database;
use Elabftw\Models\Scheduler;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * The team page
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Team');
// default response is error page with general error message
$Response = new Response();
$Response->prepare($Request);

try {
    if ($App->Session->has('anon')) {
        throw new IllegalActionException('Anon user tried accessing the team page');
    }

    $Teams = new Teams($App->Users);
    $teamArr = $Teams->read();
    $teamsStats = $Teams->getStats((int) $App->Users->userData['team']);

    $Database = new Database($App->Users);
    // we only want the bookable type of items
    $Database->bookableFilter = ' AND bookable = 1';
    $Scheduler = new Scheduler($Database);

    $TagCloud = new TagCloud((int) $App->Users->userData['team']);

    $itemsArr = $Database->read();
    $itemData = null;

    $selectedItem = null;
    if ($Request->query->get('item')) {
        $Scheduler->Database->setId((int) $Request->query->get('item'));
        $selectedItem = $Request->query->get('item');
        // itemData is to display the name/category of the selected item
        $itemData = $Scheduler->Database->read();
        if (empty($itemData)) {
            throw new ImproperActionException(_('Nothing to show with this id'));
        }
    }

    $Templates = new Templates($App->Users);
    $templatesArr = $Templates->readFromTeam();

    $template = 'team.html';
    $renderArr = array(
        'TagCloud' => $TagCloud,
        'Scheduler' => $Scheduler,
        'itemsArr' => $itemsArr,
        'itemData' => $itemData,
        'selectedItem' => $selectedItem,
        'teamArr' => $teamArr,
        'teamsStats' => $teamsStats,
        'templatesArr' => $templatesArr,
        'lang' => Tools::getCalendarLang($App->Users->userData['lang']),
    );

    $Response->setContent($App->render($template, $renderArr));
} catch (ImproperActionException $e) {
    // show message to user
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (IllegalActionException $e) {
    // log notice and show message
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error(true));
    $Response->setContent($App->render($template, $renderArr));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    // log error and show message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
    $Response->setContent($App->render($template, $renderArr));
} finally {
    $Response->send();
}
