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
require_once('inc/connect.php');
require_once('inc/functions.php');
require_once('lib/swift_required.php');
// we receive email in post
if (isset($_POST['email'])) {
    // Check that we can actually send emails :
    if (SMTP_USERNAME == 'YOURUSERNAME') {
            $msg_arr[] = 'Emails are not configured ! Configure an SMTP server first !';
            $_SESSION['errors'] = $msg_arr;
            session_write_close();
            header("location: login.php");
            die();
    }

    // // Get infos about the requester (will be sent in the mail afterwards)
    // Get IP
    if (!empty($_SERVER["HTTP_CLIENT_IP"])){
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    } else {
        $ip = $_SERVER["REMOTE_ADDR"];
    }
    // Get user agent
    $u_agent = $_SERVER['HTTP_USER_AGENT'];

    // Sanitize post
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    // Is email in database ?
    if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        // Get associated userid
        $sql = "SELECT userid,username FROM users WHERE email = :email";
        $result = $bdd->prepare($sql);
        $result->execute(array(
        'email' => $email));
        $data = $result->fetch();
        $userid = $data['userid'];
        $username = $data['username'];
        $numrows = $result->rowCount();
        // Check email exists
        if($numrows === 1){
            // Get info to build the URL
            // HTTP or HTTPS ?
            if (!empty($_SERVER['HTTPS'])) {
                $protocol = 'https://';
            } else {
                $protocol = 'http://';
            }
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
            ->setBody('Hi,
Someone (probably you) with the IP Adress : '.$ip.' and the user agent : '.$u_agent.'
requested a new password on eLabFTW.

Follow this link to change your password :
'.$reset_link.'

~~
Email sent by eLabFTW
http://www.elabftw.net
Free open-source Lab Manager');
            $transport = Swift_SmtpTransport::newInstance(SMTP_ADDRESS, SMTP_PORT, SMTP_ENCRYPTION)
            ->setUsername(SMTP_USERNAME)
            ->setPassword(SMTP_PASSWORD);
            $mailer = Swift_Mailer::newInstance($transport);
            $result = $mailer->send($message);
            // Now redirect to login page
            // Say it went well (by using the error msg array)
            $msg_arr[] = 'Email sent. Check your INBOX.';
            $_SESSION['infos'] = $msg_arr;
            session_write_close();
            header("location: login.php");
        } else {
            $msg_arr[] = 'Email not found in database !';
            $_SESSION['errors'] = $msg_arr;
            session_write_close();
            header("location: login.php");
        }
    } else {
            $msg_arr[] = 'The email address you entered was invalid !';
            $_SESSION['errors'] = $msg_arr;
            session_write_close();
            header("location: login.php");
    }
}

