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

// EMAIL
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
// Validate we were given a good email
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
            // Generate password
            $password = createPassword(8);
            // Create salt
            $salt = hash("sha512", uniqid(rand(), TRUE));
            // Create hash
            $passwordHash = hash("sha512", $salt.$password);
            // Replace new password in database
            $sql = "UPDATE users 
                SET password = :password, 
               salt = :salt 
                    WHERE userid = :userid";
            $req = $bdd->prepare($sql);
            $result = $req->execute(array(
                'password' => $passwordHash,
                'salt' => $salt,
                'userid' => $userid));
            if($result){
                // Send an email with the new password
                // Create the message
                $message = Swift_Message::newInstance()
                // Give the message a subject
                ->setSubject('[eLabFTW] Password reset')
                // Set the From address with an associative array
                ->setFrom(array('elabftw.net@gmail.com' => 'eLabFTW.net'))
                // Set the To addresses with an associative array
                ->setTo(array($email => 'Poisson Rouge'))
                // Give it a body
                ->setBody('Hi,
Someone (probably you) with the IP Adress : '.$ip.' and the user agent : '.$u_agent.'
requested a new password on eLabFTW.

You can now login with these :
Username : '.$username.'
Password : '.$password.'

You should change your password as soon as you login, and choose one you will remember !

~~
Email sent by eLabFTW
http://www.elabftw.net
Free open-source Lab Manager');
$ini_arr = parse_ini_file("admin/config.ini");
    $transport = Swift_SmtpTransport::newInstance($ini_arr['smtp_address'], $ini_arr['smtp_port'], $ini_arr['smtp_encryption'])
          ->setUsername($ini_arr['smtp_username'])
            ->setPassword($ini_arr['smtp_password']);
                $mailer = Swift_Mailer::newInstance($transport);
                $result = $mailer->send($message);
                // Now redirect to login page
                // Say it went well (by using the error msg array)
                $errmsg_arr[] = 'New password sent. Check your emails.';
                $_SESSION['infos'] = $errmsg_arr;
                session_write_close();
                header("location: login.php");
            }else{
                $errmsg_arr[] = 'Something went wrong with the database query. Check the flux capacitor.';
                $_SESSION['infos'] = $errmsg_arr;
                session_write_close();
                header("location: login.php");
            }
        }else{
                $errmsg_arr[] = 'Email not found in database !';
                $_SESSION['infos'] = $errmsg_arr;
                session_write_close();
                header("location: login.php");
        }
    }else{
                $errmsg_arr[] = 'The email address you entered was invalid !';
                $_SESSION['infos'] = $errmsg_arr;
                session_write_close();
                header("location: login.php");
    }
