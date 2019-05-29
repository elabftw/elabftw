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
use Elabftw\Models\Status;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * CRUD for Status
 * Only Ajax request and json responses here
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    if (!$App->Session->get('is_admin')) {
        throw new IllegalActionException('Non admin user tried to access admin panel.');
    }

    $Status = new Status($App->Users);

    // CREATE STATUS
    if ($Request->request->has('statusCreate')) {
        $Status->create(
            $Request->request->get('name'),
            $Request->request->get('color'),
            (int) $Request->request->get('isTimestampable')
        );
    }

    // UPDATE STATUS
    if ($Request->request->has('statusUpdate')) {
        $Status->update(
            (int) $Request->request->get('id'),
            $Request->request->get('name'),
            $Request->request->get('color'),
            (int) $Request->request->get('isTimestampable'),
            (int) $Request->request->get('isDefault')
        );
    }

    // DESTROY STATUS
    if ($Request->request->has('statusDestroy')) {
        $Status->destroy((int) $Request->request->get('id'));
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
