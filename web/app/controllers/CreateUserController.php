<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Elabftw\Services\Check;
use Exception;
use Swift_TransportException;
use Symfony\Component\HttpFoundation\RedirectResponse;

require_once \dirname(__DIR__) . '/init.inc.php';

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

    // CSRF
    $App->Csrf->validate();

    if ((Check::id((int) $Request->request->get('team')) === false) ||
        !$Request->request->get('firstname') ||
        !$Request->request->get('lastname') ||
        !$Request->request->get('email') ||
        !filter_var($Request->request->get('email'), FILTER_VALIDATE_EMAIL)) {
        throw new ImproperActionException(_('A mandatory field is missing!'));
    }

    // Create user
    $userid = $App->Users->create(
        $Request->request->get('email'),
        array($Request->request->get('team')),
        $Request->request->get('firstname'),
        $Request->request->get('lastname'),
        '',
        (int) $Request->request->get('usergroup'),
    );

    $App->Session->getFlashBag()->add('ok', _('Account successfully created'));

    // log user creation
    $App->Log->info('New user created');
} catch (Swift_TransportException $e) {
    // for swift error, don't display error to user as it might contain sensitive information
    // but log it and display general error. See #841
    $App->Log->error('', array('exception' => $e));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} catch (ImproperActionException | InvalidCsrfTokenException $e) {
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
