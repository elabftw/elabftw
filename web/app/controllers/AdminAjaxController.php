<?php
/**
 * app/controllers/AdminController.php
 *
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
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Deal with requests sent from the admin page
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved')
));

try {

    if (!$App->Session->get('is_admin')) {
        throw new IllegalActionException('Non admin user tried to access admin controller.');
    }

    // CSRF
    $App->Csrf->validate();

    // UPDATE ORDERING
    if ($Request->request->has('updateOrdering')) {
        if ($Request->request->get('table') === 'status') {
            $Entity = new Status($App->Users);
        } elseif ($Request->request->get('table') === 'items_types') {
            $Entity = new ItemsTypes($App->Users);
        } else {
            throw new IllegalActionException('Bad table for updateOrdering.');
        }

        $Entity->updateOrdering($Request->request->all());
    }

    // UPDATE COMMON TEMPLATE
    if ($Request->request->has('commonTplUpdate')) {
        $Templates = new Templates($App->Users);
        $Templates->updateCommon($Request->request->get('commonTplUpdate'));
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
