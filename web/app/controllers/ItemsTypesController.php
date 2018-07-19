<?php
/**
 * app/controllers/ItemsTypesController.php
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
 * Deal with ajax requests sent from the admin page
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();

try {
    $ItemsTypes = new ItemsTypes($App->Users);

    $res = false;
    $msg = Tools::error();

    if (!$Session->get('is_admin')) {
        throw new Exception('Non admin user tried to access admin panel.');
    }

    // CREATE ITEMS TYPES
    if ($Request->request->has('itemsTypesCreate')) {
        if ($ItemsTypes->create(
            $Request->request->get('name'),
            $Request->request->get('color'),
            $Request->request->get('bookable'),
            $Request->request->get('template')
        )) {
            $res = true;
            $msg = _('Saved');
        }
    }

    // UPDATE ITEM TYPE
    if ($Request->request->has('itemsTypesUpdate')) {
        if ($ItemsTypes->update(
            $Request->request->get('id'),
            $Request->request->get('name'),
            $Request->request->get('color'),
            $Request->request->get('bookable'),
            $Request->request->get('template')
        )) {
            $res = true;
            $msg = _('Saved');
        }
    }

    // DESTROY ITEM TYPE
    if ($Request->request->has('itemsTypesDestroy')) {
        try {
            $ItemsTypes->destroy($Request->request->get('id'));
            $res = true;
            $msg = _('Item type deleted successfully');
        } catch (Exception $e) {
            $msg = $e->getMessage();
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
