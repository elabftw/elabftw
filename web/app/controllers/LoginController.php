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

use Elabftw\Controllers\LoginController;
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
    $Response = new LoginController($App->Config->configArr, $App->Request, $App->Session, $App->demoMode)->getResponse();
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
