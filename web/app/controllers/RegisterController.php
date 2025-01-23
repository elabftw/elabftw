<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Params\UserParams;
use Elabftw\Services\Check;
use Elabftw\Services\TeamsHelper;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

use function dirname;

require_once dirname(__DIR__) . '/init.inc.php';

// default location to redirect to
$location = '/login.php';

try {
    // check for disabled local register
    if ($App->Config->configArr['local_register'] === '0') {
        throw new ImproperActionException(_('Registration is disabled.'));
    }

    // Stop bot registration by checking if the (invisible to humans) bot input is filled
    if (!empty($App->Request->request->get('bot'))) {
        throw new IllegalActionException('The bot field was filled on register page. Possible automated registration attempt.');
    }

    if (Check::id($App->Request->request->getInt('team')) === false
        || !$App->Request->request->getString('firstname')
        || !$App->Request->request->getString('lastname')
        || !$App->Request->request->getString('email')
        || !filter_var($App->Request->request->get('email'), FILTER_VALIDATE_EMAIL)
    ) {
        throw new ImproperActionException(_('A mandatory field is missing!'));
    }

    // Check that the user is being added to a team available in team addition dropdowns.
    $teamsHelper = new TeamsHelper($App->Request->request->getInt('team'));
    $teamsHelper->teamIsVisibleOrExplode();

    // Create user
    $App->Users->createOne(
        (new UserParams('email', $App->Request->request->getString('email')))->getStringContent(),
        array($App->Request->request->getInt('team')),
        (new UserParams('firstname', $App->Request->request->getString('firstname')))->getStringContent(),
        (new UserParams('lastname', $App->Request->request->getString('lastname')))->getStringContent(),
        (new UserParams('password', $App->Request->request->getString('password')))->getStringContent(),
    );

    if ($App->Users->needValidation) {
        $App->Session->getFlashBag()->add('ok', _('Registration successful :)<br>Your account must now be validated by an admin.<br>You will receive an email when it is done.'));
    } else {
        $App->Session->getFlashBag()->add('ok', _('Registration successful :)<br>Welcome to eLabFTW o/'));
    }
    // store the email here so we can put it in the login field
    $App->Session->set('email', $App->Request->request->getString('email'));
} catch (ImproperActionException $e) {
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
    $location = '/register.php';
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->getMessage())));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
    $location = '/register.php';
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array('Exception' => $e));
    $App->Session->getFlashBag()->add('ko', Tools::error());
    $location = '/register.php';
} finally {
    $Response = new RedirectResponse($location);
    $Response->send();
}
