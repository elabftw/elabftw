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
/* install/testSQL.php to test if the SQL/email parameters are good */

// Check if there is already a config file, die if yes.
if(file_exists('../admin/config.php')) {
    die();
}

// MYSQL
if (isset($_POST['mysql'])) {
    try
    {
        $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $bdd = new PDO('mysql:host='.$_POST['db_host'].';dbname='.$_POST['db_name'], $_POST['db_user'], $_POST['db_password'], $pdo_options);
    }
    catch(Exception $e)
    {
        echo $e->getMessage();
        exit();
    }
    echo 1;
}


// EMAIL
if (isset($_POST['email'])) {
    require_once '../lib/swift_required.php';
    // Create the message
    $message = Swift_Message::newInstance()
    // Give the message a subject
    ->setSubject('[eLabFTW] Test email')
    // Set the From address with an associative array
    ->setFrom(array('elabftw.net@gmail.com' => 'eLabFTW.net'))
    // Set the To addresses with an associative array
    ->setTo(array('elabftw-test@yopmail.com' => 'Test'))
    // Give it a body
    ->setBody('If you are reading this, then you correctly configured the email settings of your eLabFTW install :).

    ~~
    Email sent by eLabFTW
    http://www.elabftw.net
    Free open-source Lab Manager');
    $transport = Swift_SmtpTransport::newInstance($_POST['smtp_address'], $_POST['smtp_port'], $_POST['smtp_encryption'])
    ->setUsername($_POST['smtp_username'])
    ->setPassword($_POST['smtp_password']);
    $mailer = Swift_Mailer::newInstance($transport);
    $result = $mailer->send($message);

    // TODO catch exception to show it
    if ($result) {
        echo 1;
    } else {
        echo 0;
    }
}

