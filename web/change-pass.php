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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\ResetPasswordKey;
use Exception;
use Symfony\Component\HttpFoundation\Response;

use function time;

/**
 * Form to reset the password
 */
require_once 'app/init.inc.php';

$Response = new Response();

try {
    $Response->prepare($Request);
    if ($App->Config->configArr['local_auth_enabled'] === '0') {
        throw new ImproperActionException('This instance has disabled local authentication method, so passwords cannot be reset.');
    }
    if ($App->demoMode) {
        throw new DemoModeException();
    }
    // make sure this page is accessed with a key
    if (!$App->Request->query->has('key')) {
        throw new IllegalActionException('Bad parameters in url.');
    }

    // validate the key to show error if the key is expired
    $ResetPasswordKey = new ResetPasswordKey(time(), Env::asString('SECRET_KEY'));
    $ResetPasswordKey->validate($App->Request->query->getAlnum('key'));

    $passwordComplexity = PasswordComplexity::from((int) $App->Config->configArr['password_complexity_requirement']);
    $template = 'change-pass.html';
    $renderArr = array(
        'key' => $App->Request->query->getAlnum('key'),
        'pageTitle' => _('Reset password'),
        'passwordInputHelp' => $passwordComplexity->toHuman(),
        'passwordInputPattern' => $passwordComplexity->toPattern(),
    );
    $Response->setContent($App->render($template, $renderArr));
} catch (AppException $e) {
    $Response = $e->getResponseFromException($App);
} catch (Exception $e) {
    $Response = $App->getResponseFromException($e);
} finally {
    $Response->send();
}
