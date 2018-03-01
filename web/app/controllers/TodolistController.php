<?php
/**
 * app/controllers/TodolistController.php
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
 *
 */
try {
    require_once '../../app/init.inc.php';

    $Todolist = new Todolist($App->Users);
    $Response = new JsonResponse();

    $res = false;
    $msg = Tools::error();

    // CREATE
    if ($Request->request->has('create')) {
        $id = $Todolist->create($Request->request->get('body'));
        if ($id) {
            $res = true;
            $msg = $id;
        }
    }

    // UPDATE
    if ($Request->request->has('update')) {
        try {
            $body = $Request->request->filter('body', null, FILTER_SANITIZE_STRING);

            if (strlen($body) === 0 || $body === ' ') {
                throw new Exception('Body is too short');
            }

            $id_arr = explode('_', $Request->request->get('id'));
            if (Tools::checkId($id_arr[1]) === false) {
                throw new Exception(_('The id parameter is invalid'));
            }
            $id = $id_arr[1];

            if ($Todolist->update($id, $body)) {
                $res = true;
                $msg = _('Saved');
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
        }
    }

    // UPDATE ORDERING
    if ($Request->request->has('updateOrdering')) {
        if ($Todolist->updateOrdering($Request->request->all())) {
            $res = true;
            $msg = _('Saved');
        }
    }

    // DESTROY
    if ($Request->request->has('destroy')) {
        if ($Todolist->destroy($Request->request->get('id'))) {
            $res = true;
            $msg = _('Item deleted successfully');
        }
    }

    // DESTROY ALL
    if ($Request->request->has('destroyAll')) {
        if ($Todolist->destroyAll()) {
            $res = true;
            $msg = _('Item deleted successfully');
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
