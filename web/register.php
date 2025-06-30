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
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Local account creation page
 */
require_once 'app/init.inc.php';

/** @psalm-suppress UncaughtThrowInGlobalScope */
$Response = new Response();
$Response->prepare($App->Request);

$template = 'error.html';
$renderArr = array();
try {
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
} catch (ImproperActionException $e) {
    $renderArr['error'] = $e->getMessage();
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array('Exception' => $e));
    $renderArr['error'] = Tools::error();
} finally {
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
