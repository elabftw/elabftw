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

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use function dirname;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users;
use Elabftw\Services\Email;
use Exception;
use Swift_Message;
use Symfony\Component\HttpFoundation\RedirectResponse;

require_once dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse('../../login.php');

try {
    $Email = new Email($App->Config, new Users());

    if ($Request->request->has('email')) {
        $email = $Request->request->get('email');

        // check email is valid. Input field is of type email so browsers should not let users send invalid email.
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ImproperActionException(_('Email provided is invalid.'));
        }

        // Get data from user
        $App->Users->populateFromEmail($email);

        // If you are not validated, the password reset form won't work
        // this is because often users don't understand that their account needs to be
        // validated and just reset their password twenty times
        if ($App->Users->userData['validated'] === '0') {
            throw new ImproperActionException('Your account is not validated. An admin of your team needs to validate it!');
        }

        // Get IP
        if ($Request->server->has('HTTP_CLIENT_IP')) {
            $ip = $Request->server->get('HTTP_CLIENT_IP');
        } elseif ($Request->server->has('HTTP_X_FORWARDED_FOR')) {
            $ip = $Request->server->get('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $Request->server->get('REMOTE_ADDR');
        }

        // the key (token) is the encrypted user's mail address
        $key = Crypto::encrypt($email, Key::loadFromAsciiSafeString(\SECRET_KEY));

        // the deadline is the encrypted epoch of now +1 hour
        $deadline = \time() + 3600;
        $deadline = Crypto::encrypt((string) $deadline, Key::loadFromAsciiSafeString(\SECRET_KEY));

        // build the reset link
        $resetLink = Tools::getUrl($Request) . '/change-pass.php';
        // not pretty but gets the job done
        $resetLink = str_replace('app/controllers/', '', $resetLink);
        $resetLink .= '?key=' . $key . '&deadline=' . $deadline . '&userid=' . $App->Users->userData['userid'];

        // Send an email with the reset link
        // Create the message
        $footer = "\n\n~~~\nSent from eLabFTW https://www.elabftw.net\n";
        $message = (new Swift_Message())
        // Give the message a subject
        ->setSubject('[eLabFTW] Password reset')
        // Set the From address with an associative array
        ->setFrom(array($App->Config->configArr['mail_from'] => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($email => $App->Users->userData['fullname']))
        // Give it a body
        ->setBody(sprintf(_('Hi. Someone (probably you) with the IP address: %s and user agent %s requested a new password on eLabFTW. Please follow this link to reset your password : %s'), $ip, $Request->server->get('HTTP_USER_AGENT'), $resetLink) . $footer);
        // now we try to send the email
        $Email->send($message);

        $App->Session->getFlashBag()->add('ok', _('Email sent. Check your INBOX.'));
    }

    // second part, update the password
    if ($Request->request->has('password')) {
        // verify both passwords are the same
        // and show useful error message if not
        if ($Request->request->get('password') !== $Request->request->get('cpassword')) {
            throw new ImproperActionException(_('The passwords do not match!'));
        }
        $App->Users->populate((int) $Request->request->get('userid'));

        // Validate key
        if ($App->Users->userData['email'] != Crypto::decrypt($Request->request->get('key'), Key::loadFromAsciiSafeString(\SECRET_KEY))) {
            throw new ImproperActionException(_('Wrong key for resetting password'));
        }

        // check deadline here too (fix #297)
        $deadline = Crypto::decrypt($Request->request->get('deadline'), Key::loadFromAsciiSafeString(SECRET_KEY));

        if ($deadline < time()) {
            throw new ImproperActionException(_('Invalid link. Reset links are only valid for one hour.'));
        }

        // Replace new password in database
        $App->Users->updatePassword($Request->request->get('password'));
        $App->Log->info('Password was changed for this user', array('userid' => $App->Session->get('userid')));
        $App->Session->getFlashBag()->add('ok', _('New password inserted. You can now login.'));
    }
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
