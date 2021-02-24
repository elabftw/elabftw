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
$location = '../../login.php';

try {
    // check for disabled local register
    if ($App->Config->configArr['local_register'] === '0') {
        throw new ImproperActionException(_('Registration is disabled.'));
    }

    // Stop bot registration by checking if the (invisible to humans) bot input is filled
    if (!empty($Request->request->get('bot'))) {
        throw new IllegalActionException('The bot field was filled on register page. Possible automated registration attempt.');
    }

    // CSRF
    $App->Csrf->validate();

    // Check if email domain is correct
    if ($App->Config->configArr['email_domain']) {
        $splitEmail = explode('@', $Request->request->get('email'));
        $splitDomains = explode(',', $App->Config->configArr['email_domain']);
        if (!in_array($splitEmail[1], $splitDomains, true)) {
            throw new ImproperActionException(_('This email domain is not allowed.'));
        }
    }

    if ((Check::id((int) $Request->request->get('team')) === false) ||
        !$Request->request->get('firstname') ||
        !$Request->request->get('lastname') ||
        !$Request->request->get('email') ||
        !filter_var($Request->request->get('email'), FILTER_VALIDATE_EMAIL)) {
        throw new ImproperActionException(_('A mandatory field is missing!'));
    }

    // Create user
    $App->Users->create(
        $Request->request->get('email'),
        array($Request->request->get('team')),
        $Request->request->get('firstname'),
        $Request->request->get('lastname'),
        $Request->request->get('password') ?? '',
    );

    if ($App->Users->needValidation) {
        $App->Session->getFlashBag()->add('ok', _('Registration successful :)<br>Your account must now be validated by an admin.<br>You will receive an email when it is done.'));
    } else {
        $App->Session->getFlashBag()->add('ok', _('Registration successful :)<br>Welcome to eLabFTW o/'));
    }
    // store the email here so we can put it in the login field
    $App->Session->set('email', $Request->request->get('email'));

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
    $location = '../../register.php';
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->getMessage())));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
    $location = '../../register.php';
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array('Exception' => $e));
    $App->Session->getFlashBag()->add('ko', Tools::error());
    $location = '../../register.php';
} finally {
    $Response = new RedirectResponse($location);
    $Response->send();
}
