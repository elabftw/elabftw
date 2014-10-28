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
require_once 'inc/connect.php';
require_once 'inc/functions.php';
require_once 'lang/'.get_config('lang').'.php';

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
    } else {
        die(_("Userid is not valid."));
    }
    // Replace new password in database
    $sql = "UPDATE users 
            SET password = :password, 
            salt = :salt 
            WHERE userid = :userid";
    $req = $pdo->prepare($sql);
    $result = $req->execute(array(
        'password' => $passwordHash,
        'salt' => $salt,
        'userid' => $userid));
    if($result){
        dblog('Info', $userid, 'Password was changed for this user.');
        $msg_arr[] = RESET_SUCCESS;
        $_SESSION['infos'] = $msg_arr;
        header("location: login.php");
    }
}
