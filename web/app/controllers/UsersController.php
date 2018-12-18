<?php
/**
 * app/controllers/UsersController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Users infos from admin or sysadmin page
 */
require_once \dirname(__DIR__) . '/init.inc.php';

if ($Request->request->has('fromSysconfig')) {
    $location = "../../sysconfig.php?tab=3";
} else {
    $location = "../../admin.php?tab=3";
}

$Response = new RedirectResponse($location);

try {

    if (!$App->Csrf->validate($Request->request->get('csrf'))) {
        throw new IllegalActionException('CSRF token validation failure.');
    }

    // UPDATE USERS
    if ($Request->request->has('usersUpdate')) {
        // you need to be at least admin to validate a user
        if (!$Session->get('is_admin')) {
            throw new IllegalActionException('Non admin user tried to edit user.');
        }

        $targetUser = new Users((int) $Request->request->get('userid'));
        // check we edit user of our team
        if (($App->Users->userData['team'] !== $targetUser->userData['team']) && !$Session->get('is_sysadmin')) {
            throw new IllegalActionException('User tried to edit user from other team.');
        }
        if ($targetUser->update($Request->request->all())) {
             $Session->getFlashBag()->add('ok', _('Saved'));
        } else {
             $Session->getFlashBag()->add('ko', Tools::error());
        }

    }

} catch (ImproperActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('ImproperAction', $e->__toString())));
    // show message to user
    $Session->getFlashBag()->add('ko', $e->__toString());

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->__toString())));
    $Session->getFlashBag()->add('ko', Tools::error(true));

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e->__toString())));
    $Session->getFlashBag()->add('ko', Tools::error());

} finally {
    $Response->send();
}
