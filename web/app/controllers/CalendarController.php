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

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Calendar;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for the calendar
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';
$App->pageTitle = _('Calendar');
$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    $Calendar = new Calendar($App->Users);

    // CREATE EVENT.
    if ($Request->request->has('create')) {
        $eventId = $Calendar->createEvent(
            $Request->request->get('start'),
            $Request->request->get('end'),
            $Request->request->get('title')
        );
        $Response->setData(array(
            'res' => true,
            'msg' => _('Saved'),
            'id'  => $eventId,
        ));
    }

    // GET EVENTS
    if ($Request->query->has('start') && $Request->query->has('end')) {
        $start = $Request->query->get('start');
        $end = $Request->query->get('end');
        $Response->setData($Calendar->readAllFromUser($start, $end));
        $Response->send();
    }
} catch (ImproperActionException $e) {
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(true),
    ));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
} finally {
    $Response->send();
}
