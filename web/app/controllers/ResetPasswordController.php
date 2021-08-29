<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use function dirname;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\QuantumException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\ExistingUser;
use Elabftw\Models\Users;
use Elabftw\Services\Email;
use Elabftw\Services\ResetPasswordKey;
use Exception;
use function nl2br;
use function random_int;
use const SECRET_KEY;
use function sleep;
use Swift_Message;
use Symfony\Component\HttpFoundation\RedirectResponse;
use function time;

require_once dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse('../../login.php');
$ResetPasswordKey = new ResetPasswordKey(time(), SECRET_KEY);

try {
    $Email = new Email($App->Config, new Users());

    // PART 1: we receive the email from the login page/forgot password form
    if ($Request->request->has('email')) {
        $email = $Request->request->get('email');

        // check email is valid. Input field is of type email so browsers should not let users send invalid email.
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ImproperActionException(_('Email provided is invalid.'));
        }

        // Get user data from provided email
        try {
            $Users = ExistingUser::fromEmail($email);
            // don't disclose if the email exists in the db or not
        } catch (ResourceNotFoundException $e) {
            // make the response slow to emulate an email being sent if there was an account associated
            sleep(random_int(1, 6));
            throw new QuantumException(_('If the account exists, an email has been sent.'));
        }

        // If user is not validated, the password reset form won't work
        // this is because often users don't understand that their account needs to be
        // validated and just reset their password twenty times
        if ($Users->userData['validated'] === '0') {
            throw new ImproperActionException(_('Your account is not validated. An admin of your team needs to validate it!'));
        }

        $key = $ResetPasswordKey->generate($Users->userData['email']);

        // build the reset link
        $resetLink = Tools::getUrl($Request) . '/change-pass.php';
        // not pretty but gets the job done
        $resetLink = str_replace('app/controllers/', '', $resetLink);
        $resetLink .= '?key=' . $key;
        $htmlResetLink = '<a href="' . $resetLink . '">' . _('Reset password') . '</a>';

        $rawBody = _('Hi. Someone (probably you) requested a new password on eLabFTW.%s Please follow this link to reset your password: %s %sThis link is only valid for %s minutes.');
        $htmlBody = sprintf($rawBody, '<br>', $htmlResetLink, '<br>', $ResetPasswordKey::LINK_LIFETIME);
        $textBody = sprintf($rawBody, "\n", $resetLink, "\n", $ResetPasswordKey::LINK_LIFETIME);

        // Send an email with the reset link
        // Create the message
        $message = (new Swift_Message())
        // Give the message a subject
        ->setSubject('[eLabFTW] Password reset')
        // Set the From address with an associative array
        ->setFrom(array($App->Config->configArr['mail_from'] => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($email => $Users->userData['fullname']))
        // Give it a body
        ->setBody($htmlBody . nl2br($Email->footer), 'text/html')
        // also add a text body
        ->addPart($textBody . $Email->footer, 'text/plain');
        // now we try to send the email
        $Email->send($message);

        // log the IP for the sysadmin to know who requested it
        // it's also good to keep a trace of such requests
        $App->Log->info(sprintf('Password reset was requested'), array('email' => $email));
        // show the same message as if the email didn't exist in the db
        // this is done to prevent information disclosure
        throw new QuantumException(_('If the account exists, an email has been sent.'));
    }

    // PART 2: update the password
    if ($Request->request->has('password')) {
        // verify the key received is valid
        // we get the Users object from the email encrypted in the key
        $Users = $ResetPasswordKey->validate($Request->request->get('key'));
        // Replace new password in database
        $Users->updatePassword($Request->request->get('password'));
        $App->Log->info('Password was changed for this user', array('userid' => $Users->userData['userid']));
        $App->Session->getFlashBag()->add('ok', _('New password inserted. You can now login.'));
    }
} catch (QuantumException $e) {
    $App->Session->getFlashBag()->add('ok', $e->getMessage());
} catch (ImproperActionException $e) {
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (Exception $e) {
    $App->Log->warning('Reset password failed attempt', array(array('ip' => $Request->server->get('REMOTE_ADDR')), array('exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} finally {
    $Response->send();
}
