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
if ($_SESSION['is_admin'] != 1) {die('You are not admin !');} // only admin can use this
$msg_arr = array();

// VALIDATE USERS
if (isset($_POST['validate'])) {
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
// called from ajax
if (isset($_POST['deluser']) && filter_var($_POST['deluser'], FILTER_VALIDATE_INT)) {
    $userid = $_POST['deluser'];
    $msg_arr = array();
    // DELETE USER
    $sql = "DELETE FROM users WHERE userid = ".$userid;
    $req = $bdd->prepare($sql);
    $req->execute();
    $sql = "DELETE FROM experiments_tags WHERE userid = ".$userid;
    $req = $bdd->prepare($sql);
    $req->execute();
    $sql = "DELETE FROM experiments WHERE userid = ".$userid;
    $req = $bdd->prepare($sql);
    $req->execute();
    // get all filenames
    $sql = "SELECT long_name FROM uploads WHERE userid = :userid AND type = :type";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'userid' => $userid,
        'type' => 'exp'
    ));
    while($uploads = $req->fetch()){
        // Delete file
        $filepath = 'uploads/'.$uploads['long_name'];
        unlink($filepath);
    }
    $sql = "DELETE FROM uploads WHERE userid = ".$userid;
    $req = $bdd->prepare($sql);
    $req->execute();
    $msg_arr[] = 'Deleted user with user ID : '.$userid;
    $_SESSION['infos'] = $msg_arr;
}

// New Plasmids template
if (isset($_POST['pla_tpl'])) {
    require_once('inc/check_body.php'); // outputs $body
    $sql = "UPDATE items_templates SET body = :body WHERE type = 'pla'";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'body' => $body
    ));
    if ($result){
        $msg_arr[] = 'New plasmids template updated successfully.';
        $_SESSION['infos'] = $msg_arr;
        header('Location: admin.php');
        exit();
    } else { //sql fail
        $msg_arr[] = 'There was a problem in the SQL request. Report a bug !';
        $_SESSION['errors'] = $msg_arr;
        header('Location: admin.php');
        exit();
    }
}
// New antibody template
if (isset($_POST['ant_tpl'])) {
    require_once('inc/check_body.php'); // outputs $body
    $sql = "UPDATE items_templates SET body = :body WHERE type = 'ant'";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'body' => $body
    ));
    if ($result){
        $msg_arr[] = 'New antibodies template updated successfully.';
        $_SESSION['infos'] = $msg_arr;
        header('Location: admin.php');
        exit();
    } else { //sql fail
        $msg_arr[] = 'There was a problem in the SQL request. Report a bug !';
        $_SESSION['errors'] = $msg_arr;
        header('Location: admin.php');
        exit();
    }
}
