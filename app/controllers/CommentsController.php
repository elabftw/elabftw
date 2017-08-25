<?php
/**
 * app/controllers/CommentsController.php
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
 * Controller for the experiments comments
 *
 */
require_once '../../app/init.inc.php';

try {

    $Entity = new Experiments($Users);
    $Response = new JsonResponse();

    $res = false;
    $msg = Tools::error();

    // CREATE
    if ($Request->request->has('create')) {
        $Entity->setId($Request->request->get('id'));
        if ($Entity->Comments->create($Request->request->get('comment'))) {
            $res = true;
            $msg = _('Saved');
        }
    }

    // UPDATE
    if ($Request->request->has('update')) {
        if ($Entity->Comments->update($Request->request->get('commentsUpdate'), $Request->request->get('id'))) {
            $res = true;
            $msg = _('Saved');
        }
    }

    // DESTROY
    if ($Request->request->has('destroy')) {
        if ($Entity->Comments->destroy($Request->request->get('id'))) {
            $res = true;
            $msg = _('Comment successfully deleted');
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
