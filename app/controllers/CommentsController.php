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
try {
    require_once '../../app/init.inc.php';

    $Comments = new Comments(new Experiments($Users));
    $Response = new JsonResponse();

    $res = false;
    $msg = Tools::error();

    // CREATE
    if ($Request->request->has('create')) {
        $Comments->Entity->setId($Request->request->get('id'));
        if ($Comments->create($Request->request->get('comment'))) {
            $res = true;
            $msg = _('Saved');
        }
    }

    // UPDATE
    if ($Request->request->has('update')) {
        if ($Comments->update($Request->request->get('commentsUpdate'), $Request->request->get('id'))) {
            $res = true;
            $msg = _('Saved');
        }
    }

    // DESTROY
    if ($Request->request->has('destroy')) {
        if ($Comments->destroy($Request->request->get('id'), $Session->get('userid'))) {
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
    $Logs = new Logs();
    $Logs->create('Error', $Session->get('userid'), $e->getMessage());
}
