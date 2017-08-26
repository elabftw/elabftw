<?php
/**
 * app/controllers/ResetPasswordController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Swift_Message;
use Exception;
use Defuse\Crypto\Crypto as Crypto;
use Defuse\Crypto\Key as Key;
use Symfony\Component\HttpFoundation\RedirectResponse;

try {
    require_once '../../app/init.inc.php';
    $Email = new Email($Config);
    $Users = new Users(null, $Auth, $Config);

    if ($Request->request->has('email')) {

        $email = $Request->request->get('email');

        // check email is valid. Input field is of type email so browsers should not let users send invalid email.
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email provided is invalid.');
        }

        // Get data from user
        $user = $Users->readFromEmail($email);

        // Is email in database ?
        if (empty($user)) {
            throw new Exception(_('Email not found in database!'));
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
        $key = Crypto::encrypt($email, Key::loadFromAsciiSafeString(SECRET_KEY));

        // the deadline is the encrypted epoch of now +1 hour
        $deadline = Crypto::encrypt(time() + 3600, Key::loadFromAsciiSafeString(SECRET_KEY));

        // build the reset link
        $resetLink = 'https://' . $Request->getHttpHost() . '/change-pass.php';
        $resetLink .='?key=' . $key . '&deadline=' . $deadline . '&userid=' . $user['userid'];

        // Send an email with the reset link
        // Create the message
        $footer = "\n\n~~~\nSent from eLabFTW https://www.elabftw.net\n";
        $message = Swift_Message::newInstance()
        // Give the message a subject
        ->setSubject('[eLabFTW] Password reset for ' . $user['fullname'])
        // Set the From address with an associative array
        ->setFrom(array($Email->Config->configArr['mail_from'] => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($email => $user['fullname']))
        // Give it a body
        ->setBody(sprintf(_('Hi. Someone (probably you) with the IP address: %s and user agent %s requested a new password on eLabFTW. Please follow this link to reset your password : %s'), $ip, $Request->server->get('HTTP_USER_AGENT'), $resetLink) . $footer);
        // generate Swift_Mailer instance
        $mailer = $Email->getMailer();
        // now we try to send the email
        if (!$mailer->send($message)) {
            throw new Exception(_('There was a problem sending the email! Error was logged.'));
        }

        $Session->getFlashBag()->add('ok', _('Email sent. Check your INBOX.'));
    }

    // second part, update the password
    if ($Request->request->has('password') &&
        $Request->request->get('password') === $Request->request->get('cpassword')) {

        $Users->setId($Request->request->get('userid'));

        // Validate key
        if ($Users->userData['email'] != Crypto::decrypt($Request->request->get('key'), Key::loadFromAsciiSafeString(SECRET_KEY))) {
            throw new Exception('Wrong key for resetting password');
        }

        // check deadline here too (fix #297)
        $deadline = Crypto::decrypt($Request->request->get('deadline'), Key::loadFromAsciiSafeString(SECRET_KEY));

        if ($deadline < time()) {
            throw new Exception(_('Invalid link. Reset links are only valid for one hour.'));
        }

        // Replace new password in database
        if (!$Users->updatePassword($Request->request->get('password'), $Request->request->get('userid'))) {
            throw new Exception('Error updating password');
        }

        $App->Logs->create('Info', $Users->userData['email'], 'Password was changed for this user.');
        $Session->getFlashBag()->add('ok', _('New password inserted. You can now login.'));
    }

} catch (Exception $e) {
    // log the error
    $App->Logs->create('Error', $Request->server->get('REMOTE_ADDR'), $e->getMessage());
    $Session->getFlashBag()->add('ko', $e->getMessage());
} finally {
    $Response = new RedirectResponse("../../login.php");
    $Response->send();
}
