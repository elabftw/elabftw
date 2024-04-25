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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\Config;
use Elabftw\Services\ResetPasswordKey;
use Exception;
use Symfony\Component\HttpFoundation\Response;

use function time;

/**
 * Form to reset the password
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Reset password');

$Response = new Response();
$Response->prepare($Request);

$renderArr = array();
$template = 'change-pass.html';

try {
    // make sure this page is accessed with a key
    if (!$App->Request->query->has('key')) {
        throw new IllegalActionException('Bad parameters in url.');
    }

    // validate the key to show error if the key is expired
    $ResetPasswordKey = new ResetPasswordKey(time(), Config::fromEnv('SECRET_KEY'));
    $ResetPasswordKey->validate($App->Request->query->getAlnum('key'));

    $passwordComplexity = PasswordComplexity::from((int) $App->Config->configArr['password_complexity_requirement']);
    $renderArr = array(
        'key' => $App->Request->query->getAlnum('key'),
        'passwordInputHelp' => $passwordComplexity->toHuman(),
        'passwordInputPattern' => $passwordComplexity->toPattern(),
    );
} catch (Exception $e) {
    $template = 'error.html';
    $renderArr['error'] = $e->getMessage();
} finally {
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
