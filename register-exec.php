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
$_SESSION['prefs']['lang'] = 'en-GB';
require_once 'lang/'.$_SESSION['prefs']['lang'].'.php';
require_once 'inc/connect.php';
require_once 'inc/functions.php';
require_once 'vendor/autoload.php';

//Array to store validation errors
$msg_arr = array();
//Validation error flag
$errflag = false;

$username = '';
$firstname = '';
$lastname = '';
$email = '';
$passwordHash = '';
$salt = '';

// Check USERNAME (sanitize and validate)
if ((isset($_POST['username'])) && (!empty($_POST['username']))) {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    // Check for duplicate username in DB
    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $pdo->query($sql);
    $numrows = $result->rowCount();
    if ($result) {
        if ($numrows > 0) {
            $msg_arr[] = REGISTER_USERNAME_USED;
            $errflag = true;
        }
        $result = null;
    }
} else {
    $msg_arr[] = FIELD_MISSING;
    $errflag = true;
}
// Check team (should be an int)
if (isset($_POST['team']) &&
    !empty($_POST['team']) &&
    filter_var($_POST['team'], FILTER_VALIDATE_INT)) {
    $team = $_POST['team'];
} else {
    $team = '';
    $msg_arr[] = FIELD_MISSING;
    $errflag = true;
}
// Check FIRSTNAME (sanitize, and make it look like Firstname)
if ((isset($_POST['firstname'])) && (!empty($_POST['firstname']))) {
    // Put everything lowercase and first letter uppercase
    $firstname = ucwords(strtolower(filter_var($_POST['firstname'], FILTER_SANITIZE_STRING)));
} else {
    $msg_arr[] = FIELD_MISSING;
    $errflag = true;
}
// Check LASTNAME (sanitize, and make it look like LASTNAME)
if ((isset($_POST['lastname'])) && (!empty($_POST['lastname']))) {
    $lastname = strtoupper(filter_var($_POST['lastname'], FILTER_SANITIZE_STRING));
} else {
    $msg_arr[] = FIELD_MISSING;
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
        $result = $pdo->query($sql);
        $numrows = $result->rowCount();
        if ($result) {
            if ($numrows > 0) {
                $msg_arr[] = REGISTER_EMAIL_USED;
                $errflag = true;
            }
            $result= null;
        }
    }
} else {
    $msg_arr[] = FIELD_MISSING;
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
        if (strlen($_POST['password']) <= 7) {
            $msg_arr[] = PASSWORD_TOO_SHORT;
            $errflag = true;
        }
        // Check confirm password is same as password
        if (strcmp($_POST['password'], $_POST['cpassword']) != 0) {
            $msg_arr[] = PASSWORDS_DONT_MATCH;
            $errflag = true;
        }
    } else {
        $msg_arr[] = FIELD_MISSING;
        $errflag = true;
    }
} else {
    $msg_arr[] = FIELD_MISSING;
    $errflag = true;
}

// If there are input validations, redirect back to the registration form
if ($errflag) {
    $_SESSION['errors'] = $msg_arr;
    session_write_close();
    header("location: register.php");
    exit;
}

// Registration date is stored in epoch
$register_date = time();

// If it's the first user ever, make him sysadmin
$sql = "SELECT COUNT(*) AS usernb FROM users";
$req = $pdo->prepare($sql);
$req->execute();
$test = $req->fetch();
// if there is no users
if ($test['usernb'] == 0) {
    // we are just after install, next user will be sysadmin
    $group = 1; // sysadmins group
} else {
    // If it's the first user of a team, make him admin
    $sql = "SELECT COUNT(*) AS usernb FROM users WHERE team = :team";
    $req = $pdo->prepare($sql);
    $req->bindParam(':team', $team);
    $req->execute();
    $test = $req->fetch();
    // if there is no users
    if ($test['usernb'] == 0) {
        // the team is freshly created, next user will be admin
        $group = 2; // admins group
    } else {
        $group = 4; // users group
    }
}


// WILL NEW USER BE VALIDATED ?
// here an admin or sysadmin won't need validation
if (get_config('admin_validate')  == 1 && $group == 4) { // validation is required for normal user
    $validated = 0; // so new user will need validation
} else {
    $validated = 1;
}

// *****************
//   REGISTRATION
// *****************

$sql = "INSERT INTO users (
    `username`,
    `firstname`,
    `lastname`,
    `email`,
    `password`,
    `team`,
    `usergroup`,
    `salt`,
    `register_date`,
    `validated`
) VALUES (
    :username,
    :firstname,
    :lastname,
    :email,
    :passwordHash,
    :team,
    :usergroup,
    :salt,
    :register_date,
    :validated);";
$req = $pdo->prepare($sql);
$req->bindParam(':username', $username);
$req->bindParam(':firstname', $firstname);
$req->bindParam(':lastname', $lastname);
$req->bindParam(':email', $email);
$req->bindParam(':passwordHash', $passwordHash);
$req->bindParam(':team', $team);
$req->bindParam(':usergroup', $group);
$req->bindParam(':salt', $salt);
$req->bindParam(':register_date', $register_date);
$req->bindParam(':validated', $validated);

$result = $req->execute();

//Check whether the query was successful or not
if ($result) {
    $msg_arr = array();
    // only send an email if validation is needed and smtp config is set
    if (get_config('admin_validate') == '1' && $group == '4' && get_config('smtp_password') != '') {
        // we send an email to the admin so he can validate the user
        // get email of the admin of the team (there might be several admins, but we send only to the first one we find)
        $sql = "SELECT * FROM users WHERE `usergroup` = 1 OR `usergroup` = 2 AND `team` = :team LIMIT 1";
        $req = $pdo->prepare($sql);
        $req->bindParam(':team', $team);
        $req->execute();
        $admin = $req->fetch();
        // Create the message
        $message = Swift_Message::newInstance()
        // Give the message a subject
        ->setSubject(EMAIL_NEW_USER_SUBJECT)
        // Set the From address with an associative array
        ->setFrom(array(get_config('smtp_username') => get_config('smtp_username')))
        // Set the To addresses with an associative array
        ->setTo(array($admin['email'] => 'Admin eLabFTW'))
        // Give it a body
        ->setBody(REGISTER_EMAIL_BODY.EMAIL_FOOTER);
        $transport = Swift_SmtpTransport::newInstance(
            get_config('smtp_address'),
            get_config('smtp_port'),
            get_config('smtp_encryption')
        )
        ->setUsername(get_config('smtp_username'))
        ->setPassword(get_config('smtp_password'));
        $mailer = Swift_Mailer::newInstance($transport);
        // SEND EMAIL
        try {
            $mailer->send($message);
        } catch (Exception $e) {
            dblog('Error', 'smtp', $e->getMessage());
            $msg_arr[] = REGISTER_EMAIL_FAILED;
            $_SESSION['errors'] = $msg_arr;
            header('Location: register.php');
            exit;
        }
        $msg_arr[] = REGISTER_SUCCESS_NEED_VALIDATION;
    } else {
        $msg_arr[] = REGISTER_SUCCESS;
    }
    $_SESSION['infos'] = $msg_arr;
    $_SESSION['username'] = $username;
    header("location: login.php");
    exit;
} else {
    die(ERROR_BUG);
}
