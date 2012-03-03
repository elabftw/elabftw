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
/* admin-exec.php - for administration of the elab */
require_once('inc/common.php');
if ($_SESSION['is_admin'] != 1) {die('You are not admin !');}

// VALIDATE USERS
if (isset($_POST['validate'])) {
    $msg_arr = array();
    $sql = "UPDATE users SET validated = 1 WHERE userid = :userid";
    $req = $bdd->prepare($sql);
    foreach ($_POST['validate'] as $user) {
        $req->execute(array(
            'userid' => $user
        ));
            $msg_arr[] = 'Validated user with user ID : '.$user;
    }
    $_SESSION['infos'] = $msg_arr;
    header('Location: admin.php');
    exit();
}

// MANAGE USERS
if (isset($_GET['deluser']) && filter_var($_GET['deluser'], FILTER_VALIDATE_INT)) {
    $userid = $_GET['deluser'];
    $msg_arr = array();
    // DELETE USER
    $sql = "DELETE FROM users WHERE userid = ".$userid;
    $req = $bdd->prepare($sql);
    $req->execute();
    $msg_arr[] = 'Deleted user with user ID : '.$userid;
    $_SESSION['infos'] = $msg_arr;
    header('Location: admin.php');
    exit();
}
