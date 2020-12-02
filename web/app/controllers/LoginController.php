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

use function dirname;
use Elabftw\Controllers\LoginController;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

// This page is all about getting authenticated and then logged in
require_once dirname(__DIR__) . '/init.inc.php';

// default location for redirect
$location = '../../login.php';
$Response = new RedirectResponse($location);

try {
    // CSRF
    $App->Csrf->validate();

    $Controller = new LoginController($App);
    $Response = $Controller->getResponse();
} catch (ImproperActionException | InvalidCsrfTokenException | InvalidCredentialsException $e) {
    $Auth->increaseFailedAttempt();
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('ip' => $_SERVER['REMOTE_ADDR']), array('IllegalAction' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('ip' => $_SERVER['REMOTE_ADDR']), array('Error' => $e)));
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (Exception $e) {
    $App->Log->error('', array(array('ip' => $_SERVER['REMOTE_ADDR']), array('Exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} finally {
    $Response->send();
}
