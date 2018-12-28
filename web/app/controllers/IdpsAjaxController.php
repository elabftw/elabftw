<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Elabftw\Models\Idps;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for IDPs
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved')
));

try {
    if (!$Session->get('is_sysadmin')) {
        throw new IllegalActionException('Non sysadmin user tried to access idps controller.');
    }

    $Idps = new Idps();

    // DESTROY IDP
    if ($Request->request->has('idpsDestroy')) {
        $Idps->destroy((int) $Request->request->get('id'));
    }

} catch (ImproperActionException $e) {
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage()
    ));

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(true)
    ));

} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage()
    ));

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error()
    ));

} finally {
    $Response->send();
}
