<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any eLabFTWlater version.                 *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
session_start();
require_once 'inc/connect.php';
require_once 'inc/functions.php';
require_once 'lib/swift_required.php';

$errflag = false;

// we receive email in post
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    // // Get infos about the requester (will be sent in the mail afterwards)
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

    // Sanitize post
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    // Is email in database ?
    if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        // Get associated userid
        $sql = "SELECT userid,username FROM users WHERE email = :email";
        $result = $pdo->prepare($sql);
        $result->execute(array(
        'email' => $email));
        $data = $result->fetch();
        $userid = $data['userid'];
        $username = $data['username'];
        $numrows = $result->rowCount();
        // Check email exists
        if ($numrows === 1) {
            // Get info to build the URL
            $protocol = 'https://';
            $reset_url = $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
            // Generate unique link
            $reset_link = $protocol.str_replace('reset-pass', 'change-pass', $reset_url).'?key='.hash("sha256", uniqid(rand(), true)).'&userid='.$userid;
            // Send an email with the reset link
            // Create the message
            $message = Swift_Message::newInstance()
            // Give the message a subject
            ->setSubject('[eLabFTW] Password reset')
            // Set the From address with an associative array
            ->setFrom(array('elabftw.net@gmail.com' => 'eLabFTW.net'))
            // Set the To addresses with an associative array
            ->setTo(array($email => 'Dori'))
            // Give it a body
            ->setBody(
                'Hi,
                Someone (probably you) with the IP Adress : '.$ip.' and the user agent : '.$u_agent.'
                requested a new password on eLabFTW.

                Follow this link to change your password :
                '.$reset_link.'

                ~~
                Email sent by eLabFTW
                http://www.elabftw.net
                Free open-source Lab Manager'
            );
            $transport = Swift_SmtpTransport::newInstance(
                get_config('smtp_address'),
                get_config('smtp_port'),
                get_config('smtp_encryption')
            )
            ->setUsername(get_config('smtp_username'))
            ->setPassword(get_config('smtp_password'));
            $mailer = Swift_Mailer::newInstance($transport);
            // now we try to send the email
            try {
                $mailer->send($message);
            } catch (Exception $e) {
                // log the error
                $logline = date('Y-m-d H:i:s')  . ' - ' . $e->getMessage() . PHP_EOL;
                file_put_contents('errors.log', $logline, FILE_APPEND);
                $errflag = true;
            }
            if ($errflag) {
                // problem
                $msg_arr[] = 'There was a problem sending the email. Error was logged.';
                if (get_config('debug') == 1) {
                    $msg_arr[] = $e->getMessage();
                }
                $_SESSION['errors'] = $msg_arr;
                header('location: login.php');
            } else { // no problem
                $msg_arr[] = 'Email sent. Check your INBOX.';
                $_SESSION['infos'] = $msg_arr;
                header("location: login.php");
            }
        } else {
            $msg_arr[] = 'Email not found in database !';
            $_SESSION['errors'] = $msg_arr;
            header("location: login.php");
        }
    } else {
            $msg_arr[] = 'The email address you entered was invalid !';
            $_SESSION['errors'] = $msg_arr;
            header("location: login.php");
    }
} else { // this page isn't called with POST
    header('Location: experiments.php');
}
