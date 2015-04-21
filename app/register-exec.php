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
require_once '../inc/connect.php';
require_once '../inc/functions.php';
require_once '../inc/locale.php';
require_once '../vendor/autoload.php';

$crypto = new \Elabftw\Elabftw\Crypto();

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

// Stop bot registration by checking if the (invisible to humans) bot input is filled
if (isset($_POST['bot']) && !empty($_POST['bot'])) {
    exit;
}

// Check USERNAME (sanitize and validate)
if ((isset($_POST['username'])) && !empty($_POST['username'])) {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    // Check for duplicate username in DB
    $sql = "SELECT * FROM users WHERE username= :username";
    $req = $pdo->prepare($sql);
    $req->bindParam(':username', $username);
    $result = $req->execute();
    $numrows = $req->rowCount();
    if ($result) {
        if ($numrows > 0) {
            $msg_arr[] = _('Username already in use!');
            $errflag = true;
        }
    }
} else {
    $msg_arr[] = _('A mandatory field is missing!');
    $errflag = true;
}
// Check TEAM (should be an int)
if (isset($_POST['team']) &&
    !empty($_POST['team']) &&
    filter_var($_POST['team'], FILTER_VALIDATE_INT)) {
    $team = $_POST['team'];
} else {
    $team = '';
    $msg_arr[] = _('A mandatory field is missing!');
    $errflag = true;
}
// Check FIRSTNAME (sanitize, and make it look like Firstname)
if ((isset($_POST['firstname'])) && (!empty($_POST['firstname']))) {
    // Put everything lowercase and first letter uppercase
    $firstname = ucwords(strtolower(filter_var($_POST['firstname'], FILTER_SANITIZE_STRING)));
} else {
    $msg_arr[] = _('A mandatory field is missing!');
    $errflag = true;
}
// Check LASTNAME (sanitize, and make it look like _('Lastname'))
if ((isset($_POST['lastname'])) && (!empty($_POST['lastname']))) {
    $lastname = strtoupper(filter_var($_POST['lastname'], FILTER_SANITIZE_STRING));
} else {
    $msg_arr[] = _('A mandatory field is missing!');
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
        $sql = "SELECT * FROM users WHERE email = :email";
        $req = $pdo->prepare($sql);
        $req->bindParam(':email', $email);
        $result = $req->execute();
        $numrows = $req->rowCount();
        if ($result) {
            if ($numrows > 0) {
                $msg_arr[] = _('Someone is already using that email address!');
                $errflag = true;
            }
        }
    }
} else {
    $msg_arr[] = _('A mandatory field is missing!');
    $errflag = true;
}

// Check PASSWORDS
if ((isset($_POST['cpassword'])) && (!empty($_POST['cpassword']))) {
    if ((isset($_POST['password'])) && (!empty($_POST['password']))) {
        // Create salt
        $salt = hash("sha512", uniqid(rand(), true));
        // Create hash
        $passwordHash = hash("sha512", $salt . $_POST['password']);
        // Check for password length
        if (strlen($_POST['password']) <= 7) {
            $msg_arr[] = _('Password must contain at least 8 characters.');
            $errflag = true;
        }
        // Check confirm password is same as password
        if (strcmp($_POST['password'], $_POST['cpassword']) != 0) {
            $msg_arr[] = _('The passwords do not match!');
            $errflag = true;
        }
    } else {
        $msg_arr[] = _('A mandatory field is missing!');
        $errflag = true;
    }
} else {
    $msg_arr[] = _('A mandatory field is missing!');
    $errflag = true;
}

// If there are input validations, redirect back to the registration form
if ($errflag) {
    $_SESSION['errors'] = $msg_arr;
    session_write_close();
    header("location: ../register.php");
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
if (get_config('admin_validate') == 1 && $group == 4) { // validation is required for normal user
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
        $sql = "SELECT * FROM users WHERE (`usergroup` = 1 OR `usergroup` = 2) AND `team` = :team LIMIT 1";
        $req = $pdo->prepare($sql);
        $req->bindParam(':team', $team);
        $req->execute();
        $admin = $req->fetch();
        // Create the message
        $footer = "\n\n~~~\nSent from eLabFTW http://www.elabftw.net\n";
        $message = Swift_Message::newInstance()
        // Give the message a subject
        ->setSubject(_('[eLabFTW] New user registered'))
        // Set the From address with an associative array
        ->setFrom(array(get_config('mail_from') => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($admin['email'] => 'Admin eLabFTW'))
        // Give it a body
        ->setBody(_('Hi. A new user registered on elabftw. Head to the admin panel to validate the account.') . $footer);
        // generate Swift_Mailer instance
        $mailer = getMailer();
        // SEND EMAIL
        try {
            $mailer->send($message);
        } catch (Exception $e) {
            dblog('Error', 'smtp', $e->getMessage());
            $msg_arr[] = _('Could not send email to inform admin. Error was logged. Contact an admin directly to validate your account.');
            $_SESSION['errors'] = $msg_arr;
            header('Location: ../register.php');
            exit;
        }
        $msg_arr[] = _('Registration successful :)<br>Your account must now be validated by an admin.<br>You will receive an email when it is done.');
    } else {
        $msg_arr[] = _('Registration successful :)<br>Welcome to eLabFTW o/');
    }
    $_SESSION['infos'] = $msg_arr;
    $_SESSION['username'] = $username;
    header("location: ../login.php");
    exit;
} else {
    die(sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug."), "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>"));
}
