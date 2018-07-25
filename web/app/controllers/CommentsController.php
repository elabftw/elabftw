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
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();

try {

    if ($App->Session->has('anon')) {
        throw new Exception(Tools::error(true));
    }

    if ($Request->request->get('type') === 'experiments') {
        $Entity = new Experiments($App->Users);
    } else {
        $Entity = new Database($App->Users);
    }

    $res = false;
    $msg = Tools::error();

    // CREATE
    if ($Request->request->has('create')) {
        $Entity->setId((int) $Request->request->get('id'));
        $commentId = $Entity->Comments->create($Request->request->get('comment'));
        $res = true;
        $msg = $commentId;
    }

    // UPDATE
    if ($Request->request->has('update')) {
        if ($Entity->Comments->update($Request->request->get('update'), $Request->request->get('id'))) {
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

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
}
$Response->send();
