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

use Elabftw\AuditEvent\PasswordResetRequested;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\QuantumException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\Config;
use Elabftw\Models\ExistingUser;
use Elabftw\Services\Email;
use Elabftw\Services\ResetPasswordKey;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;

use Symfony\Component\Mime\Email as Memail;

use function dirname;
use function nl2br;
use function random_int;
use function sleep;

use function time;

require_once dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse('/login.php');
$ResetPasswordKey = new ResetPasswordKey(time(), Config::fromEnv('SECRET_KEY'));

try {
    $Email = new Email(
        new Mailer(Transport::fromDsn($App->Config->getDsn())),
        $App->Log,
        $App->Config->configArr['mail_from'],
    );

    // PART 1: we receive the email from the login page/forgot password form
    if ($App->Request->request->has('email')) {
        $email = $App->Request->request->getString('email');

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
            sleep(random_int(1, 3));
            throw new QuantumException(_('If the account exists, an email has been sent.'));
        }

        // If user is not validated, the password reset form won't work
        // this is because often users don't understand that their account needs to be
        // validated and just reset their password twenty times
        if ($Users->userData['validated'] === 0) {
            throw new ImproperActionException(_('Your account is not validated. An admin of your team needs to validate it!'));
        }

        $key = $ResetPasswordKey->generate($Users->userData['email']);

        // build the reset link
        $resetLink = Config::fromEnv('SITE_URL') . '/change-pass.php?key=' . $key;
        $htmlResetLink = '<a href="' . $resetLink . '">' . _('Reset password') . '</a>';

        $rawBody = _('Hi. Someone (probably you) requested a new password on eLabFTW.%s Please follow this link to reset your password: %s %sThis link is only valid for %s minutes.');
        $htmlBody = sprintf($rawBody, '<br>', $htmlResetLink, '<br>', $ResetPasswordKey::LINK_LIFETIME);
        $textBody = sprintf($rawBody, "\n", $resetLink, "\n", $ResetPasswordKey::LINK_LIFETIME);

        // Send an email with the reset link
        // Create the message
        $message = (new Memail())
        ->subject('[eLabFTW] Password reset')
        ->from(new Address($App->Config->configArr['mail_from'], 'eLabFTW'))
        ->to(new Address($email, $Users->userData['fullname']))
        ->html($htmlBody . nl2br($Email->footer))
        ->text($textBody . $Email->footer);
        $Email->send($message);

        // keep a trace of the request
        AuditLogs::create(new PasswordResetRequested($email));
        // show the same message as if the email didn't exist in the db
        // this is done to prevent information disclosure
        throw new QuantumException(_('If the account exists, an email has been sent.'));
    }

    // PART 2: update the password
    if ($App->Request->request->has('password')) {
        // verify the key received is valid
        // we get the Users object from the email encrypted in the key
        $Users = $ResetPasswordKey->validate($App->Request->request->getString('key'));
        // Replace new password in database
        // make sure the new password is not the same as the old one
        // but only if we're in a required reset process
        if ($App->Session->has('renew_password_required')) {
            $Users->requireResetPassword($App->Request->request->getString('password'));
            $App->Session->remove('renew_password_required');
        } else {
            $Users->resetPassword($App->Request->request->getString('password'));
        }
        $App->Session->getFlashBag()->add('ok', _('New password inserted. You can now login.'));
    }
} catch (QuantumException $e) {
    $App->Session->getFlashBag()->add('ok', $e->getMessage());
} catch (ImproperActionException $e) {
    // show message to user and redirect to the change pass page
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
    $Response = new RedirectResponse('/change-pass.php?key=' . $App->Request->request->getString('key'));
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (Exception $e) {
    $App->Log->warning('Reset password failed attempt', array(array('ip' => $App->Request->server->get('REMOTE_ADDR')), array('exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} finally {
    $Response->send();
}
