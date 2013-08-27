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
session_start();
require_once('inc/connect.php');
if (!isset($_SESSION['auth'])) {
$ini_arr = parse_ini_file('admin/config.ini');
}

//Array to store validation errors
$msg_arr = array();
//Validation error flag
$errflag = false;

// Check USERNAME (sanitize and validate)
    if ((isset($_POST['username'])) && (!empty($_POST['username']))) {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    // Check for duplicate username in DB
    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $bdd->query($sql);
    $numrows = $result->rowCount(); 
    if($result) {
        if($numrows > 0) {
            $msg_arr[] = 'Username already in use';
            $errflag = true;
        }
        $result = null;
    }
} else {
    $msg_arr[] = 'Username missing';
    $errflag = true;
}
// Check FIRSTNAME (sanitize, and make it look like Firstname)
    if ((isset($_POST['firstname'])) && (!empty($_POST['firstname']))) {
    // Put everything lowercase and first letter uppercase
    $firstname = ucwords(strtolower(filter_var($_POST['firstname'], FILTER_SANITIZE_STRING)));
} else {
    $msg_arr[] = 'Firstname missing';
    $errflag = true;
}
// Check LASTNAME (sanitize, and make it look like LASTNAME)
    if ((isset($_POST['lastname'])) && (!empty($_POST['lastname']))) {
    $lastname = strtoupper(filter_var($_POST['lastname'], FILTER_SANITIZE_STRING));
} else {
    $msg_arr[] = 'Lastname missing';
    $errflag = true;
}

// Check EMAIL (sanitize and validate)
if ((isset($_POST['email'])) && (!empty($_POST['email']))) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg_arr[] = 'Email seems to be invalid';
        $errflag = true;
    } else {
    // Check for duplicate email in DB
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $bdd->query($sql);
    $numrows = $result->rowCount(); 
    if($result) {
        if($numrows > 0) {
            $msg_arr[] = 'Someone is already using that email address !';
            $errflag = true;
        }
        $result= null;
    }
    }
} else {
    $msg_arr[] = 'Email missing';
    $errflag = true;
}

// Check PASSWORDS
if ((isset($_POST['cpassword'])) && (!empty($_POST['cpassword']))) {
    if ((isset($_POST['password'])) && (!empty($_POST['password']))) {
        // Create salt
        $salt = hash("sha512", uniqid(rand(), true));
        // Create hash
        $passwordHash = hash("sha512", $salt.$_POST['password']);
        // Check for password length
        if (strlen($_POST['password']) <= 3) {
            $msg_arr[] = 'Password must contain at least 4 characters';
            $errflag = true;
        }
        // Check confirm password is same as password
        if (strcmp($_POST['password'], $_POST['cpassword']) != 0 ) {
            $msg_arr[] = 'Passwords do not match';
            $errflag = true;
        }
    } else {
        $msg_arr[] = 'Password missing';
        $errflag = true;
    }
} else {
    $msg_arr[] = 'Confirmation password missing';
    $errflag = true;
}

// If there are input validations, redirect back to the registration form
if($errflag) {
    $_SESSION['errors'] = $msg_arr;
    session_write_close();
    header("location: register.php");
    exit();
}

// Registration date is stored in epoch
$register_date = time();
// If it's the first user, make him admin (call from install/index.php)
$sql = "SELECT COUNT(*) FROM users WHERE is_admin = 1";
$req = $bdd->prepare($sql);
$req->execute();
$test = $req->fetch();
// if there is no admin
if ($test[0] == 0) {
    // next user will be admin
    $is_admin = 1;
} else {
    $is_admin = 0;
}

// If all is good => registration
if ($ini_arr['admin_validate'] === '1'){
    $sql = "INSERT INTO users(username, firstname, lastname, email, password, salt, register_date, is_admin) VALUES('$username', '$firstname', '$lastname', '$email', '$passwordHash', '$salt', '$register_date', '$is_admin')";
} else { // no admin validation in config file
    $sql = "INSERT INTO users(username, firstname, lastname, email, password, salt, register_date, validated, is_admin) VALUES('$username', '$firstname', '$lastname', '$email', '$passwordHash', '$salt', '$register_date', '1', '$is_admin')";
}

$result = $bdd->exec($sql);
//Check whether the query was successful or not
if($result) {
    /*
    // Send email
        require_once('lib/swift_required.php');
        // Create the message
        $message = Swift_Message::newInstance()
        // Give the message a subject
        ->setSubject('Welcome to eLabFTW !')
        // Set the From address with an associative array
        //->setFrom(array('nicolas.carpi@curie.fr' => 'Nicolas CARPi'))
        ->setFrom(array('elabftw.net@gmail.com' => 'eLabFTW.net'))
        // Set the To addresses with an associative array
        ->setTo(array($email => $firstname))
        // Give it a body
        ->setBody('Congratulations ! You registered successfully to eLabFTW \o/');
$transport = Swift_SmtpTransport::newInstance($ini_arr['smtp_address'], $ini_arr['smtp_port'], $ini_arr['smtp_encryption'])
    ->setUsername($ini_arr['smtp_username'])
    ->setPassword($ini_arr['smtp_password']);
        $mailer = Swift_Mailer::newInstance($transport);
        $result = $mailer->send($message);
     */
    // Redirect
        $msg_arr = array();
        if ($ini_arr['admin_validate'] === '1'){
            $msg_arr[] = 'Registration successful :)<br />Your account must now be validated by an admin.<br />You will receive an email when it is done.';
        } else {
            $msg_arr[] = 'Registration successful :)<br />Welcome to eLabFTW \o/';
        }
        $_SESSION['infos'] = $msg_arr;
        $_SESSION['username'] = $username;
        header("location: login.php");
}else {
    die("Query failed");
}

