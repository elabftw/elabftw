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

use Elabftw\Enums\PasswordComplexity;
use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\DemoModeException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Local account creation page
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    $Response->prepare($App->Request);
    // Check if we're logged in
    if ($App->Session->has('is_auth')) {
        throw new ImproperActionException(sprintf(
            _('Please %slogout%s before you register another account.'),
            "<a href='app/logout.php'>",
            '</a>'
        ));
    }

    // local register might be disabled
    if ($App->Config->configArr['local_register'] === '0') {
        throw new ImproperActionException(_('No local account creation is allowed!'));
    }
    // or we might be in demo mode
    if ($App->demoMode) {
        throw new DemoModeException();
    }

    $passwordComplexity = PasswordComplexity::from((int) $App->Config->configArr['password_complexity_requirement']);

    $template = 'register.html';
    $renderArr = array(
        'hideTitle' => true,
        'pageTitle' => _('Register'),
        'passwordInputHelp' => $passwordComplexity->toHuman(),
        'passwordInputPattern' => $passwordComplexity->toPattern(),
        'privacyPolicy' => $App->Config->configArr['privacy_policy'] ?? '',
        'teamsArr' => $App->Teams->readAllVisible(),
    );
    $Response->setContent($App->render($template, $renderArr));
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
