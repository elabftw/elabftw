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
if (!isset($_SESSION)) { session_start(); }
require_once 'inc/connect.php';
require_once 'inc/functions.php';
// formkey stuff
require_once 'lib/classes/formkey.class.php';
$formKey = new formKey();

//Array to store validation errors
$msg_arr = array();
//Validation error flag
$errflag = false;

// Check the form_key
if (!isset($_POST['form_key']) || !$formKey->validate()) {
    // form key is invalid
    $msg_arr[] = 'The form key is invalid !';
    $errflag = true;
}


// Check USERNAME (sanitize and validate)
if ((isset($_POST['username'])) && (!empty($_POST['username']))) {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
} else {
    $msg_arr[] = 'Username missing';
    $errflag = true;
}

// Check PASSWORD is sent
    if ((!isset($_POST['password'])) || (empty($_POST['password']))) {
        $msg_arr[] = 'Password missing';
        $errflag = true;
    }

//If there are input validations, redirect back to the login form
if($errflag) {
    $_SESSION['errors'] = $msg_arr;
    session_write_close();
    header("location: login.php");
    exit();
}

// SQL for verification + actual login with cookies
// Get salt
$sql = "SELECT salt FROM users WHERE username='$username'";
$result = $bdd->prepare($sql);
$result->execute();
$data = $result->fetch();
$salt = $data['salt'];
// Create hash
$passwordHash = hash("sha512", $salt.$_POST['password']);

// admin validated ?
if (get_config('admin_validate') == 1){
    $sql = "SELECT * FROM users WHERE username='$username' AND password='$passwordHash' AND validated= 1";
} else {
    $sql = "SELECT * FROM users WHERE username='$username' AND password='$passwordHash'";
}
$req = $bdd->prepare($sql);
$result = $req->execute();
$numrows = $req->rowCount();
//Check whether the query was successful or not
if ($result) {
    if ($numrows === 1) {
        //Login Successful
        $data = $req->fetch();
        // Store userid and permissions in $_SESSION
        session_regenerate_id();
        $_SESSION['auth'] = 1;
        $_SESSION['path'] = get_config('path');
        $_SESSION['userid'] = $data['userid'];
        // Used in the menu
        $_SESSION['username'] = $data['username'];
        $_SESSION['is_admin'] = $data['is_admin'];
        // PREFS
        $_SESSION['prefs'] = array('theme' => $data['theme'], 
            'display' => $data['display'], 
            'order' => $data['order_by'], 
            'sort' => $data['sort_by'], 
            'limit' => $data['limit_nb'], 
            'shortcuts' => array('create' => $data['sc_create'], 'edit' => $data['sc_edit'], 'submit' => $data['sc_submit'], 'todo' => $data['sc_todo']));
        session_write_close();
        // Make a unique token and store it in sql AND cookie
        $token = md5(uniqid(rand(), true));
        // Cookie validity = 1 month, works only in https
        if (!isset($_SERVER['HTTPS'])) {
            die("eLabFTW works only in HTTPS. Please enable HTTPS on your server (<a href='https://httpd.apache.org/docs/current/ssl/ssl_howto.html'>see documentation</a>). Or retry with https:// in front of the address.");
        }

        // Set the two cookies : token and path
        // setcookie( $name, $value, $expire, $path, $domain, $secure, $httponly )
        setcookie('token', $token, time() + 60*60*24*30, null, null, true, true);
        //setcookie('token', $token, time() + 60*60*24*30, dirname(__FILE__), null, true, true);
        setcookie('path', dirname(__FILE__), time() + 60*60*24*30, null, null, true, true);
        // Update the token in SQL
        $sql = "UPDATE users SET token = :token WHERE userid = :userid";
        $req = $bdd->prepare($sql);
        $req->execute(array(
            'token' => $token,
            'userid' => $data['userid']
        ));
        header("location: experiments.php");
    } else {
        //Login failed
        $msg_arr = array();
        $msg_arr[] = "Login failed. Either you mistyped your password, or your account isn't activated yet.";
        if (!isset($_SESSION['failed_attempt'])) {
            $_SESSION['failed_attempt'] = 1;
        } else {
            $_SESSION['failed_attempt'] += 1;
        }

        $_SESSION['errors'] = $msg_arr;
        header("location: login.php");
    }
} else {
    die("Query failed");
}

