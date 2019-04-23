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
use Elabftw\Models\Users;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Users info from admin or sysadmin page
 */
require_once \dirname(__DIR__) . '/init.inc.php';

if ($Request->request->has('fromSysconfig')) {
    $location = "../../sysconfig.php?tab=3";
} else {
    $location = "../../admin.php?tab=3";
}

$Response = new RedirectResponse($location);

try {

    // CSRF
    $App->Csrf->validate();

    // UPDATE USERS
    if ($Request->request->has('usersUpdate')) {
        // you need to be at least admin to validate a user
        if (!$Session->get('is_admin')) {
            throw new IllegalActionException('Non admin user tried to edit user.');
        }

        $targetUser = new Users((int) $Request->request->get('userid'), new Auth($Request, $Session));
        // check we edit user of our team
        if (($App->Users->userData['team'] !== $targetUser->userData['team']) && !$Session->get('is_sysadmin')) {
            throw new IllegalActionException('User tried to edit user from other team.');
        }
        $targetUser->update($Request->request->all());
    }

    $Session->getFlashBag()->add('ok', _('Saved'));

} catch (ImproperActionException $e) {
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));

} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $App->Session->getFlashBag()->add('ko', $e->getMessage());

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());

} finally {
    $Response->send();
}
