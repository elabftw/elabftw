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
require_once '../inc/common.php';

$formKey = new \Elabftw\Elabftw\FormKey();
$user = new \Elabftw\Elabftw\User();

//Array to store validation errors
$msg_arr = array();
//Validation error flag
$errflag = false;

// Check the form_key
if (!isset($_POST['form_key']) || !$formKey->validate()) {
    // form key is invalid
    $msg_arr[] = _("Your session expired. Please retry.");
    $errflag = true;
}

// Check username (sanitize and validate)
if ((isset($_POST['username'])) && (!empty($_POST['username']))) {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
} else {
    $username = '';
    $msg_arr[] = _('A mandatory field is missing!');
    $errflag = true;
}

// Check password is sent
if ((!isset($_POST['password'])) || (empty($_POST['password']))) {
    $msg_arr[] = _('A mandatory field is missing!');
    $errflag = true;
}

//If there are input validation errors, redirect back to the login form
if ($errflag) {
    $_SESSION['errors'] = $msg_arr;
    session_write_close();
    header("location: ../login.php");
    exit;
}

// the actual login
if ($user->login($username, $_POST['password'])) {
    if (isset($_COOKIE['redirect'])) {
        $location = $_COOKIE['redirect'];
    } else {
        $location = '../experiments.php';
    }
    header('location: ' . $location);
    exit;
} else {
    // log the attempt if the login failed
    dblog('Warning', $_SERVER['REMOTE_ADDR'], 'Failed login attempt');
    // inform the user
    $msg_arr[] = _("Login failed. Either you mistyped your password or your account isn't activated yet.");
    if (!isset($_SESSION['failed_attempt'])) {
        $_SESSION['failed_attempt'] = 1;
    } else {
        $_SESSION['failed_attempt'] += 1;
    }
    $_SESSION['errors'] = $msg_arr;

    header("location: ../login.php");
    exit;
}
