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
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Elabftw\Services\UsersHelper;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Users info from admin or sysadmin page with ajax request and json response
 */
require_once dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    // you need to be at least admin to validate/archive/delete a user
    if (!$App->Session->get('is_admin')) {
        throw new IllegalActionException('Non admin user tried to edit another user.');
    }
    $targetUser = new Users((int) $Request->request->get('userid'));
    $UsersHelper = new UsersHelper((int) $targetUser->userData['userid']);
    $targetUserTeams = $UsersHelper->getTeamsIdFromUserid();

    // check we edit user of our team
    if (!in_array((string) $App->Users->userData['team'], $targetUserTeams, true) && !$App->Session->get('is_sysadmin')) {
        throw new IllegalActionException('User tried to edit user from other team.');
    }

    // UPDATE USERS
    if ($Request->request->has('usersUpdate')) {
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

    // VALIDATE USER
    if ($Request->request->has('usersValidate')) {
        $targetUser->validate();
    }

    // ARCHIVE USER TOGGLE
    if ($Request->request->has('toggleArchiveUser')) {
        if ($targetUser->userData['validated'] === '0') {
            throw new ImproperActionException('You are trying to archive an unvalidated user. Maybe you want to delete the account?');
        }

        $targetUser->toggleArchive();

        // if we are archiving a user, also lock all experiments
        if ($targetUser->userData['archived'] === '0') {
            $targetUser->lockExperiments();
        }
    }

    // DESTROY
    if ($Request->request->has('destroyUser')) {
        if ($targetUser->userData['is_sysadmin']) {
            throw new IllegalActionException('Sysadmin users cannot be deleted!');
        }

        $targetUser->destroy();
    }
} catch (ImproperActionException | UnauthorizedException $e) {
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(true),
    ));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(),
    ));
} finally {
    $Response->send();
}
