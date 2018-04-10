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
namespace Elabftw\Elabftw;

use Exception;

/**
 * Controller for IDPs
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

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
            $Request->request->get('id'),
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
        if ($Idps->destroy($Request->request->get('id'))) {
            $Session->getFlashBag()->add('ok', _('Configuration updated successfully.'));
        } else {
            $Session->getFlashBag()->add('ko', Tools::error());
        }
    }

} catch (Exception $e) {
    $App->Logs->create('Error', $Session->get('userid'), $e->getMessage());
    // we can show error message to sysadmin
    $Session->getFlashBag()->add('ko', $e->getMessage());
} finally {
    header('Location: ../../sysconfig.php?tab=8');
}
