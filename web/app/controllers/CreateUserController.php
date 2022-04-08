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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\ValidatedUser;
use Elabftw\Services\Check;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

require_once dirname(__DIR__) . '/init.inc.php';

// default location to redirect to
$location = '../../admin.php?tab=3';
// user can also be created from sysconfig page
if ($Request->request->get('frompage') === 'sysconfig.php') {
    $location = '../../sysconfig.php?tab=3';
}

try {
    if (!$App->Session->get('is_admin')) {
        throw new IllegalActionException('Non admin user tried to create a user.');
    }
    if (!$App->Session->get('is_sysadmin') && $App->Config->configArr['admins_create_users'] === '0') {
        throw new IllegalActionException('Admin tried to create user directly');
    }
    // check if we are admin of the correct team
    if (!$App->Session->get('is_sysadmin') && (int) $Request->request->get('team') !== $App->Users->userData['team']) {
        throw new IllegalActionException('Admin tried to create user in another team');
    }
    $usergroup = Check::usergroupOrExplode((int) $Request->request->get('usergroup'));
    // a non sysadmin cannot promote someone to sysadmin
    if ($usergroup === 1 && $App->Session->get('is_sysadmin') !== '1') {
        throw new IllegalActionException('Only a sysadmin can put someone sysadmin.');
    }

    if ((Check::id((int) $Request->request->get('team')) === false) ||
        !$Request->request->get('firstname') ||
        !$Request->request->get('lastname') ||
        !$Request->request->get('email') ||
        !filter_var($Request->request->get('email'), FILTER_VALIDATE_EMAIL)) {
        throw new ImproperActionException(_('A mandatory field is missing!'));
    }

    // Create user
    $Users = ValidatedUser::fromAdmin(
        $Request->request->get('email'),
        array($Request->request->get('team')),
        $Request->request->get('firstname'),
        $Request->request->get('lastname'),
        $usergroup,
    );

    $App->Session->getFlashBag()->add('ok', _('Account successfully created'));

    // log user creation
    $App->Log->info('New user created');
} catch (ImproperActionException $e) {
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->getMessage())));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array('Exception' => $e));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} finally {
    $Response = new RedirectResponse($location);
    $Response->send();
}
