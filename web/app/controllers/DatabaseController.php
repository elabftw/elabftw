<?php
/**
 * app/controllers/DatabaseController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Database
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();

try {
    if ($App->Session->has('anon')) {
        throw new Exception(Tools::error(true));
    }

    $Entity = new Database($App->Users);
    if ($Request->request->has('id')) {
        $Entity->setId((int) $Request->request->get('id'));
    }

    // CREATE
    if ($Request->query->has('create')) {
        $id = $Entity->create($Request->query->get('create'));
        $Response = new RedirectResponse("../../database.php?mode=edit&id=" . $id);
    }

    // UPDATE RATING
    if ($Request->request->has('rating')) {
        $Response = new JsonResponse();
        $Entity->canOrExplode('write');

        if ($Entity->updateRating($Request->request->get('rating'))) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            $Response->setData(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
    $Session->getFlashBag()->add('ko', Tools::error());
    $Response = new RedirectResponse("../../database.php");
}
$Response->send();
