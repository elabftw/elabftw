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

$password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
$cpassword = filter_var($_POST['cpassword'], FILTER_SANITIZE_STRING);
if ($password == $cpassword) {
    // BUILD PASSWORD
    // Create salt
    $salt = hash("sha512", uniqid(rand(), true));
    // Create hash
    $passwordHash = hash("sha512", $salt.$password);
    // Get userid
    if (filter_var($_POST['userid'], FILTER_VALIDATE_INT)) {
        $userid = $_POST['userid'];
    }
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
        // LOGIN THE USER
        // admin validated ?
        $ini_arr = parse_ini_file('admin/config.ini');
        if ($ini_arr['admin_validate'] === '1'){
        $sql = "SELECT * FROM users WHERE userid='$userid' AND password='$passwordHash' AND validated= 1";
        } else {
        $sql = "SELECT * FROM users WHERE userid='$userid' AND password='$passwordHash'";
        }
        $req = $bdd->prepare($sql);
        $result = $req->execute();
        $numrows = $req->rowCount();
        //Check whether the query was successful or not
        if ($result) {
            if ($numrows === 1) {
                $data = $req->fetch();
                // Store userid and permissions in $_SESSION
                session_regenerate_id();
                $_SESSION['auth'] = 1;
                $_SESSION['path'] = $ini_arr['path'];
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
                // Make a unique token and store it in sql AND cookie
                $token = md5(uniqid(rand(), true));
                // Cookie validity = 1 month
                setcookie('token', $token, time() + 60*60*24*30);
                $path = dirname(__FILE__);
                setcookie('path', $path, time() + 60*60*24*30);
                $sql = "UPDATE users SET token = :token WHERE userid = :userid";
                $req = $bdd->prepare($sql);
                $req->execute(array(
                    'token' => $token,
                    'userid' => $data['userid']
                ));
                $msg_arr[] = 'New password updated. You have been automagically logged in. Welcome back ;)';
                $_SESSION['infos'] = $msg_arr;
                session_write_close();
                header("location: experiments.php");
            }
        }
    }
}

