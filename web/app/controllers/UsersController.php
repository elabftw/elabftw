<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use function dirname;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Elabftw\Services\UsersHelper;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Users info from admin or sysadmin page
 */
require_once dirname(__DIR__) . '/init.inc.php';

if ($Request->request->has('fromSysconfig')) {
    $location = '../../sysconfig.php?tab=3';
} else {
    $location = '../../admin.php?tab=3';
}

$Response = new RedirectResponse($location);

try {
    // UPDATE USERS
    if ($Request->request->has('usersUpdate')) {
        // you need to be at least admin to edit a user
        if (!$App->Session->get('is_admin')) {
            throw new IllegalActionException('Non admin user tried to edit user.');
        }

        $targetUser = new Users((int) $Request->request->get('userid'));
        $UsersHelper = new UsersHelper((int) $targetUser->userData['userid']);
        $targetUserTeams = $UsersHelper->getTeamsIdFromUserid();
        // check we edit user of our team
        if (!in_array((string) $App->Users->userData['team'], $targetUserTeams, true) && !$App->Session->get('is_sysadmin')) {
            throw new IllegalActionException('User tried to edit user from other team.');
        }
        // a non sysadmin cannot promote someone to sysadmin
        $usergroup = Check::usergroupOrExplode((int) $Request->request->get('usergroup'));
        if ($usergroup === 1 && $App->Session->get('is_sysadmin') !== '1') {
            throw new ImproperActionException(_('Only a sysadmin can put someone sysadmin.'));
        }
        // a non sysadmin cannot demote a sysadmin
        if ($targetUser->userData['is_sysadmin'] && $usergroup !== 1 && $App->Session->get('is_sysadmin') !== '1') {
            throw new IllegalActionException('Only a sysadmin can demote another sysadmin.');
        }

        $targetUser->updateUser($Request->request->all());
    }

    $App->Session->getFlashBag()->add('ok', _('Saved'));
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
