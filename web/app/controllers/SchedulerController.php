<?php
/**
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
use Elabftw\Models\Users;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for the scheduler
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    $Database = new Database($App->Users);
    $Scheduler = new Scheduler($Database);

    // CREATE
    if ($Request->request->has('create')) {
        $Database->setId((int) $Request->request->get('item'));
        $Scheduler->create(
            $Request->request->get('start'),
            $Request->request->get('end'),
            $Request->request->get('title')
        );
    }

    // READ
    if ($Request->request->has('read')) {
        $Database->setId((int) $Request->request->get('item'));
        $Response->setData($Scheduler->read());
    }

    // UPDATE START
    if ($Request->request->has('updateStart')) {
        $Scheduler->setId((int) $Request->request->get('id'));
        $eventArr = $Scheduler->readFromId();
        if ($eventArr['userid'] === $App->Session->get('userid')) {
            $Scheduler->updateStart($Request->request->get('start'), $Request->request->get('end'));
        }
    }
    // UPDATE END
    if ($Request->request->has('updateEnd')) {
        $Scheduler->setId((int) $Request->request->get('id'));
        $eventArr = $Scheduler->readFromId();
        if ($eventArr['userid'] === $App->Session->get('userid')) {
            $Scheduler->updateEnd($Request->request->get('end'));
        }
    }
    // DESTROY
    if ($Request->request->has('destroy')) {
        $Scheduler->setId((int) $Request->request->get('id'));
        $eventArr = $Scheduler->readFromId();
        if ($eventArr['userid'] === $Session->get('userid')) {
            $Scheduler->destroy();
        } elseif ($Session->get('is_admin')) {
            // check user is in our team
            $Me = new Users((int) $Session->get('userid'));
            $Booker = new Users((int) $eventArr['userid']);
            if ($Booker->userData['team'] === $Me->userData['team']) {
                $Scheduler->destroy();
            } else {
                throw new ImproperActionException(Tools::error(true));
            }
        } else {
            throw new ImproperActionException(Tools::error(true));
        }
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
