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

use Elabftw\Auth\AnonymousLoginValidator;
use Elabftw\Auth\LoginFlow;
use Elabftw\Auth\MfaPolicy;
use Elabftw\Auth\MfaVerifier;
use Elabftw\Auth\PasswordRenewalPolicy;
use Elabftw\Auth\RememberMe;
use Elabftw\Auth\SamlRequestState;
use Elabftw\Auth\SelectableTeamsProvider;
use Elabftw\Auth\UserLoginValidator;
use Elabftw\Controllers\LoginController;
use Elabftw\Enums\EnforceMfa;
use Elabftw\Enums\Messages;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\InvalidDeviceTokenException;
use Elabftw\Exceptions\InvalidMfaCodeException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\AuthFail;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

use function dirname;

// This page is all about getting authenticated and then logged in
require_once dirname(__DIR__) . '/init.inc.php';

// default location for redirect
$location = '/login.php';
$Response = new RedirectResponse($location);

try {
    $enforceMfa = EnforceMfa::from((int) $App->Config->configArr['enforce_mfa']);
    $loginFlow = new LoginFlow(
        new MfaPolicy($enforceMfa),
        new PasswordRenewalPolicy((int) $App->Config->configArr['max_password_age_days']),
        new SelectableTeamsProvider(),
        new UserLoginValidator(),
    );
    $Response = new LoginController(
        $App->Config->configArr,
        $App->Request,
        $App->Session,
        $loginFlow,
        new MfaVerifier($App->Session),
        new AnonymousLoginValidator((bool) $App->Config->configArr['anon_users']),
        new RememberMe(
            $App->Request,
            $App->Config->configArr['remember_me_allowed'] === '1',
        ),
        new SamlRequestState($App->Request),
        $App->demoMode,
    )->getResponse();

} catch (InvalidCredentialsException | InvalidMfaCodeException $e) {
    $loginTries = (int) $App->Config->configArr['login_tries'];
    $AuthFail = new AuthFail($loginTries, $e->getCode(), $App->Request->cookies->getAlnum('devicetoken'));
    $AuthFail->register();
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('ip' => $App->Request->server->get('REMOTE_ADDR')), array('IllegalAction' => $e)));
    $App->Session->getFlashBag()->add('ko', Messages::InsufficientPermissions->toHuman());
} catch (ImproperActionException | InvalidDeviceTokenException | UnauthorizedException $e) {
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (DatabaseErrorException $e) {
    $App->Log->error('', array(array('ip' => $App->Request->server->get('REMOTE_ADDR')), array('Error' => $e)));
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (Exception $e) {
    $App->Log->error('', array(array('ip' => $App->Request->server->get('REMOTE_ADDR')), array('Exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Messages::GenericError->toHuman());
} finally {
    $Response->send();
}
