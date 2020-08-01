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
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

require_once dirname(__DIR__) . '/init.inc.php';

$location = '../../mfa.php';
$Response = new RedirectResponse($location);

try {
    // CSRF
    $App->Csrf->validate();

    // Check verification code
    $Mfa = new Mfa($App->Request, $App->Session);
    $verifyMFACode = $Mfa->verifyCode();

    if ($App->Session->has('enable_mfa')) {
        if ($verifyMFACode) {
            $Mfa->saveSecret();
        } else {
            $App->Session->getFlashBag()->add('ko', _('Two Factor Authentication not enabled!'));
        }
    } else {
        if ($verifyMFACode) {
            // Redirect to where one comes from
            $location = $App->Session->get('mfa_redirect');
            $App->Session->remove('mfa_redirect');
            $App->Session->remove('mfa_secret');
        } elseif (!$verifyMFACode && !$App->Session->has('auth')) {
            $Auth->increaseFailedAttempt();
        }
    }

    $Response = new RedirectResponse($location);
} catch (ImproperActionException | InvalidCsrfTokenException | InvalidCredentialsException $e) {
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
