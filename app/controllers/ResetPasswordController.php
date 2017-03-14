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

try {
    require_once '../../app/init.inc.php';
    $Config = new Config();
    $Email = new Email($Config);
    $Users = new Users(null, $Config);
    $Logs = new Logs();

    if (isset($_POST['email'])) {
        // check email is valid. Input field is of type email so browsers should not let users send invalid email.
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email provided is invalid.');
        }

        $email = $_POST['email'];

        // Get data from user
        $user = $Users->readFromEmail($email);

        // Is email in database ?
        if (empty($user)) {
            throw new Exception(_('Email not found in database!'));
        }

        // Get infos about the requester (will be sent in the mail afterwards)
        // Get IP
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Get user agent
        $u_agent = $_SERVER['HTTP_USER_AGENT'];

        // the key (token) is the encrypted user's mail address
        $key = Crypto::encrypt($email, Key::loadFromAsciiSafeString(SECRET_KEY));

        // the deadline is the encrypted epoch of now +1 hour
        $deadline = Crypto::encrypt(time() + 3600, Key::loadFromAsciiSafeString(SECRET_KEY));

        // Get info to build the URL
        $protocol = 'https://';
        $reset_url = $_SERVER['SERVER_NAME'] . Tools::getServerPort() . $_SERVER['REQUEST_URI'];
        $reset_link = $protocol .
            str_replace('app/controllers/ResetPasswordController', 'change-pass', $reset_url) .
            '?key=' . $key .
            '&deadline=' . $deadline .
            '&userid=' . $user['userid'];

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
        ->setBody(sprintf(_('Hi. Someone (probably you) with the IP address: %s and user agent %s requested a new password on eLabFTW. Please follow this link to reset your password : %s'), $ip, $u_agent, $reset_link) . $footer);
        // generate Swift_Mailer instance
        $mailer = $Email->getMailer();
        // now we try to send the email
        if (!$mailer->send($message)) {
            throw new Exception(_('There was a problem sending the email! Error was logged.'));
        }

        $_SESSION['ok'][] = _('Email sent. Check your INBOX.');
    }

    // second part, update the password
    if (isset($_POST['password']) &&
        isset($_POST['cpassword']) &&
        isset($_POST['key']) &&
        isset($_POST['userid']) &&
        $_POST['password'] === $_POST['cpassword']) {

        if (Tools::checkId($_POST['userid']) === false) {
            throw new Exception('The id parameter is invalid');
        }

        $userArr = $Users->read($_POST['userid']);

        // Validate key
        if ($userArr['email'] != Crypto::decrypt($_POST['key'], Key::loadFromAsciiSafeString(SECRET_KEY))) {
            throw new Exception('Wrong key for resetting password');
        }

        // check deadline here too (fix #297)
        $deadline = Crypto::decrypt($_POST['deadline'], Key::loadFromAsciiSafeString(SECRET_KEY));

        if ($deadline < time()) {
            throw new Exception(_('Invalid link. Reset links are only valid for one hour.'));
        }

        // Replace new password in database
        if (!$Users->updatePassword($_POST['password'], $_POST['userid'])) {
            throw new Exception('Error updating password');
        }

        $Logs->create('Info', $_POST['userid'], 'Password was changed for this user.');
        $_SESSION['ok'][] = _('New password inserted. You can now login.');
    }

} catch (Exception $e) {
    // log the error
    $Logs = new Logs();
    $Logs->create('Error', $_SERVER['REMOTE_ADDR'], $e->getMessage());
    $_SESSION['ko'][] = $e->getMessage();
} finally {
    header("location: ../../login.php");
}
