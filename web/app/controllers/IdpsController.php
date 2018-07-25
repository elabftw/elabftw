<?php
/**
 * app/controllers/IdpsController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for IDPs
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';
$redirect = true;

try {
    $Idps = new Idps();

    if (!$Session->get('is_sysadmin')) {
        throw new Exception('Non sysadmin user tried to access sysadmin controller.');
    }

    // CREATE IDP
    if ($Request->request->has('idpsCreate')) {
        if ($Idps->create(
            $Request->request->get('name'),
            $Request->request->get('entityid'),
            $Request->request->get('ssoUrl'),
            $Request->request->get('ssoBinding'),
            $Request->request->get('sloUrl'),
            $Request->request->get('sloBinding'),
            $Request->request->get('x509')
        )) {
            $Session->getFlashBag()->add('ok', _('Configuration updated successfully.'));
        } else {
            $Session->getFlashBag()->add('ko', Tools::error());
        }
    }

    // UPDATE IDP
    if ($Request->request->has('idpsUpdate')) {
        if ($Idps->update(
            (int) $Request->request->get('id'),
            $Request->request->get('name'),
            $Request->request->get('entityid'),
            $Request->request->get('ssoUrl'),
            $Request->request->get('ssoBinding'),
            $Request->request->get('sloUrl'),
            $Request->request->get('sloBinding'),
            $Request->request->get('x509')
        )) {
            $Session->getFlashBag()->add('ok', _('Configuration updated successfully.'));
        } else {
            $Session->getFlashBag()->add('ko', Tools::error());
        }
    }

    // DESTROY IDP
    if ($Request->request->has('idpsDestroy')) {
        $Response = new JsonResponse();
        $redirect = false;
        if ($Idps->destroy((int) $Request->request->get('id'))) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Item deleted successfully')
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
    // we can show error message to sysadmin
    $Session->getFlashBag()->add('ko', $e->getMessage());
}
if ($redirect) {
    $Response = new RedirectResponse('../../sysconfig.php?tab=7');
}
$Response->send();
