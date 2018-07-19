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

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Deal with requests sent from the admin page
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();

try {

    if (!$App->Session->get('is_admin')) {
        throw new Exception('Non admin user tried to access admin panel.');
    }

    // UPDATE ORDERING
    if ($Request->request->has('updateOrdering')) {
        if ($Request->request->get('table') === 'status') {
            $Entity = new Status($App->Users);
        } elseif ($Request->request->get('table') === 'items_types') {
            $Entity = new ItemsTypes($App->Users);
        }

        if ($Entity->updateOrdering($Request->request->all())) {
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

    // UPDATE TEAM SETTINGS
    if ($Request->request->has('teamsUpdateFull')) {
        $Teams = new Teams($App->Users);
        $Response = new RedirectResponse("../../admin.php?tab=1");
        if ($Teams->update($Request->request->all())) {
            $App->Session->getFlashBag()->add('ok', _('Configuration updated successfully.'));
        } else {
            $App->Session->getFlashBag()->add('ko', Tools::error());
        }
    }

    // CLEAR STAMP PASS
    if ($Request->query->get('clearStamppass')) {
        $Teams = new Teams($App->Users);
        if (!$Teams->destroyStamppass()) {
            throw new Exception('Error clearing the timestamp password');
        }
        $App->Session->getFlashBag()->add('ok', _('Configuration updated successfully.'));
        $Response = new RedirectResponse("../../admin.php?tab=1");
    }

    // UPDATE COMMON TEMPLATE
    if ($Request->request->has('commonTplUpdate')) {
        $Templates = new Templates($App->Users);
        if ($Templates->updateCommon($Request->request->get('commonTplUpdate'))) {
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
}
$Response->send();
