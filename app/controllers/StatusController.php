<?php
/**
 * app/controllers/StatusController.php
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
 * CRUD for Status
 * Only Ajax request and json responses here
 *
 */
require_once '../../app/init.inc.php';

try {
    $Status = new Status($App->Users);
    $Response = new JsonResponse();

    // default response is error
    $res = false;
    $msg = Tools::error();

    if (!$Session->get('is_admin')) {
        throw new Exception('Non admin user tried to access admin panel.');
    }

    // CREATE STATUS
    if ($Request->request->has('statusCreate')) {
        if ($Status->create(
            $Request->request->get('name'),
            $Request->request->get('color'),
            $Request->request->get('isTimestampable')
        )) {
            $res = true;
            $msg = _('Saved');
        }
    }

    // UPDATE STATUS
    if ($Request->request->has('statusUpdate')) {
        if ($Status->update(
            $Request->request->get('id'),
            $Request->request->get('name'),
            $Request->request->get('color'),
            $Request->request->get('isTimestampable'),
            $Request->request->get('isDefault')
        )) {
            $res = true;
            $msg = _('Saved');
        }
    }

    // DESTROY STATUS
    if ($Request->request->has('statusDestroy')) {
        try {
            $Status->destroy($Request->request->get('id'));
            $res = true;
            $msg = _('Status deleted successfully');
        } catch (Exception $e) {
            $msg = $e->getMessage();
        }
    }

    $Response->setData(array(
        'res' => $res,
        'msg' => $msg
    ));
    $Response->send();

} catch (Exception $e) {
    $App->Logs->create('Error', $Session->get('userid'), $e->getMessage());
}
