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

use Elabftw\Exceptions\IllegalActionException;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for IDPs
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => false,
    'msg' => Tools::error()
));

try {
    if (!$Session->get('is_sysadmin')) {
        throw new IllegalActionException('Non sysadmin user tried to access idps controller.');
    }

    $Idps = new Idps();

    // DESTROY IDP
    if ($Request->request->has('idpsDestroy')) {
        if ($Idps->destroy((int) $Request->request->get('id'))) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Item deleted successfully')
            ));
        }
    }

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->__toString())));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(true)
    ));

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e->__toString())));

} finally {
    $Response->send();
}
