<?php
/**
 * app/controllers/SchedulerController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for the scheduler
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

try {
    $Database = new Database($App->Users);
    $Scheduler = new Scheduler($Database);
    $Response = new JsonResponse();

    $res = false;
    $msg = Tools::error();

    // CREATE
    if ($Request->request->has('create')) {
        $Database->setId((int) $Request->request->get('item'));
        if ($Scheduler->create(
            $Request->request->get('start'),
            $Request->request->get('end'),
            $Request->request->get('title')
        )) {
            $res = true;
            $msg = _('Saved');
        }
    }

    // READ
    if ($Request->request->has('read')) {
        $Database->setId((int) $Request->request->get('item'));
        $Response->setData($Scheduler->read());
        $Response->send();
        exit;
    }

    // UPDATE START
    if ($Request->request->has('updateStart')) {
        $Scheduler->setId((int) $Request->request->get('id'));
        $eventArr = $Scheduler->readFromId();
        if ($eventArr['userid'] === $Session->get('userid')) {
            if ($Scheduler->updateStart($Request->request->get('start'), $Request->request->get('end'))) {
                $res = true;
                $msg = _('Saved');
            }
        }
    }
    // UPDATE END
    if ($Request->request->has('updateEnd')) {
        $Scheduler->setId((int) $Request->request->get('id'));
        $eventArr = $Scheduler->readFromId();
        if ($eventArr['userid'] == $Session->get('userid')) {
            if ($Scheduler->updateEnd($Request->request->get('end'))) {
                $res = true;
                $msg = _('Saved');
            }
        }
    }
    // DESTROY
    if ($Request->request->has('destroy')) {
        $Scheduler->setId((int) $Request->request->get('id'));
        $eventArr = $Scheduler->readFromId();
        if ($eventArr['userid'] == $Session->get('userid')) {
            if ($Scheduler->destroy()) {
                $res = true;
                $msg = _('Event deleted successfully');
            }
        }
    }

    $Response->setData(array(
        'res' => $res,
        'msg' => $msg
    ));
    $Response->send();

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
}
