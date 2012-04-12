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
*    the License, or (at your option) any later version.                        *
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
require_once('inc/common.php');
// SQL to get firstname and email
$sql = "SELECT firstname, lastname, email FROM users WHERE userid=".$_SESSION['userid'];
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();
$email = $data['email'];
$firstname = $data['firstname'];
$lastname = $data['lastname'];
$req->closeCursor();
// EMAIL
require_once('lib/swift_required.php');
// Create the message
$message = Swift_Message::newInstance()
// Give the message a subject
->setSubject('Your submission to Nature has been reviewed')
// Set the From address with an associative array
->setFrom(array('elabftw.net@gmail.com' => 'Nature Publishing Group'))
// Set the To addresses with an associative array
->setTo(array($email => $firstname.' '.$lastname))
// Give it a body
->setBody("Dear Dr.".$lastname.",
    We have thoroughly read your manuscript and although we think it is great, we cannot publish it.
    Keep it up bro.
        
Regards,
~John B. ROOT
Chief editor
Nature publishing group

* This is NOT a real email; it is a joke from elabFTW *");
// SEND
$transport = Swift_SmtpTransport::newInstance($ini_arr['smtp_address'], $ini_arr['smtp_port'], $ini_arr['smtp_encryption'])
    ->setUsername($ini_arr['smtp_username'])
    ->setPassword($ini_arr['smtp_password']);
    $mailer = Swift_Mailer::newInstance($transport);
    $result = $mailer->send($message);
    if ($result){
$msg_arr[] = "Experiment successfully sent to Nature for publication. You should get an answer promptly by email.";
$_SESSION['infos'] = $msg_arr;
session_write_close();
header('Location: experiments.php');
    } else {
$msg_arr[] = "There was an error sending the email to ".$email;
$_SESSION['errors'] = $msg_arr;
session_write_close();
header('Location: experiments.php');
    }
?>
