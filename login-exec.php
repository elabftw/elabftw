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
if (!isset($_SESSION)) {
    session_start();
}
require_once 'inc/connect.php';
require_once 'inc/functions.php';
require_once 'inc/locale.php';
// formkey stuff
require_once 'inc/classes/formkey.class.php';
$formKey = new formKey();

//Array to store validation errors
$msg_arr = array();
//Validation error flag
$errflag = false;

// Check the form_key
if (!isset($_POST['form_key']) || !$formKey->validate()) {
    // form key is invalid
    $msg_arr[] = _('The form key is invalid. Please retry.');
    $errflag = true;
}


// Check _('Username') (sanitize and validate)
if ((isset($_POST['username'])) && (!empty($_POST['username']))) {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
} else {
    $username = '';
    $msg_arr[] = _('A mandatory field is missing!');
    $errflag = true;
}

// Check _('Password') is sent
if ((!isset($_POST['password'])) || (empty($_POST['password']))) {
    $msg_arr[] = _('A mandatory field is missing!');
    $errflag = true;
}

//If there are input validations, redirect back to the login form
if ($errflag) {
    $_SESSION['errors'] = $msg_arr;
    session_write_close();
    header("location: login.php");
    exit;
}

// SQL for verification + actual login with cookies
// Get salt
$sql = "SELECT salt FROM users WHERE username='$username'";
$result = $pdo->prepare($sql);
$result->execute();
$data = $result->fetch();
$salt = $data['salt'];
// Create hash
$passwordHash = hash("sha512", $salt.$_POST['password']);

// Do we let people in if they are not validated by an admin ?
if (get_config('admin_validate') == 1) {
    $sql = "SELECT * FROM users WHERE username='$username' AND password='$passwordHash' AND validated= 1";
} else {
    $sql = "SELECT * FROM users WHERE username='$username' AND password='$passwordHash'";
}
$req = $pdo->prepare($sql);
$result = $req->execute();
$numrows = $req->rowCount();
//Check whether the query was successful or not
if ($result) {
    if ($numrows === 1) {

        // **********************
        //    _('Login') SUCCESSFUL
        // **********************

        $data = $req->fetch();
        // Store userid and permissions in $_SESSION
        session_regenerate_id();
        $_SESSION['auth'] = 1;
        $_SESSION['userid'] = $data['userid'];
        $_SESSION['team_id'] = $data['team'];
        // Used in the menu
        $_SESSION['username'] = $data['username'];
        // load permissions
        $perm_sql = "SELECT * FROM groups WHERE group_id = :group_id LIMIT 1";
        $perm_req = $pdo->prepare($perm_sql);
        $perm_req->bindParam(':group_id', $data['usergroup']);
        $perm_req->execute();
        $group = $perm_req->fetch(PDO::FETCH_ASSOC);

        $_SESSION['is_admin'] = $group['is_admin'];
        $_SESSION['is_sysadmin'] = $group['is_sysadmin'];

        // PREFS
        $_SESSION['prefs'] = array(
            'display' => $data['display'],
            'order' => $data['order_by'],
            'sort' => $data['sort_by'],
            'limit' => $data['limit_nb'],
            'shortcuts' => array('create' => $data['sc_create'], 'edit' => $data['sc_edit'], 'submit' => $data['sc_submit'], 'todo' => $data['sc_todo']),
            'lang' => $data['lang'],
            'close_warning' => intval($data['close_warning']));
        session_write_close();
        // Make a unique token and store it in sql AND cookie
        $token = md5(uniqid(rand(), true));
        // Cookie validity = 1 month, works only in https
        if (!isset($_SERVER['HTTPS'])) {
            die("eLabFTW works only in HTTPS. Please enable HTTPS on your server (<a href='https://github.com/NicolasCARPi/elabftw/wiki/Troubleshooting#wiki-switch-to-https'>see documentation</a>). Or retry with https:// in front of the address.");
        }

        // Set token cookie
        // setcookie( $name, $value, $expire, $path, $domain, $secure, $httponly )
        setcookie('token', $token, time() + 60*60*24*30, null, null, true, true);
        // Update the token in SQL
        $sql = "UPDATE users SET token = :token WHERE userid = :userid";
        $req = $pdo->prepare($sql);
        $req->execute(array(
            'token' => $token,
            'userid' => $data['userid']
        ));

        header("location: experiments.php");
        exit;
    } else { // login failed
        // log the attempt
        dblog('Warning', $_SERVER['REMOTE_ADDR'], 'Failed login attempt');

        // inform the user
        $msg_arr = array();
        $msg_arr[] = _("Login failed. Either you mistyped your password or your account isn't activated yet.");
        if (!isset($_SESSION['failed_attempt'])) {
            $_SESSION['failed_attempt'] = 1;
        } else {
            $_SESSION['failed_attempt'] += 1;
        }
        $_SESSION['errors'] = $msg_arr;

        header("location: login.php");
        exit;
    }
} else {
    die(sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug."), "<a href='https://github.com/NicolasCARPi/elabftw/issues/'>", "</a>"));
}
