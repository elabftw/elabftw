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
// for success messages
$infos_arr = array();
// for error messages
$errors_arr = array();

// VALIDATE USERS
if (isset($_POST['validate'])) {
    $sql = "UPDATE users SET validated = 1 WHERE userid = :userid";
    $req = $bdd->prepare($sql);
    foreach ($_POST['validate'] as $user) {
        $req->execute(array(
            'userid' => $user
        ));
            $infos_arr[] = 'Validated user with user ID : '.$user;
    }
    $_SESSION['infos'] = $infos_arr;
    header('Location: admin.php');
    exit();
}

// MANAGE USERS
// ////////////

// DELETE USER
// called from ajax with the javascript function confirm_delete of admin.php
if (isset($_POST['deluser']) && is_pos_int($_POST['deluser'])) {
    $userid = $_POST['deluser'];
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
    $infos_arr[] = 'Everything was purged successfully.';
    $_SESSION['infos'] = $infos_arr;
    header('Location: admin.php');
    exit();
}

// EDIT USER
if (isset($_POST['userid']) && is_pos_int($_POST['userid'])) {
    $userid = $_POST['userid'];
    $firstname = filter_var($_POST['firstname'], FILTER_SANITIZE_STRING); 
    $lastname = filter_var($_POST['lastname'], FILTER_SANITIZE_STRING); 
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if($_POST['is_admin'] == 1) {
        $is_admin = 1;
    } else { 
        $is_admin = 0;
    }
    if($_POST['validated'] == 1) {
        $validated = 1;
    } else { 
        $validated = 0;
    }
    // reset password
    if (isset($_POST['new_password']) && !empty($_POST['new_password']) && isset($_POST['confirm_new_password'])) {
        // check if passwords match
        if ($_POST['new_password'] == $_POST['confirm_new_password']) {
        // Good to go
        // Create salt
        $salt = hash("sha512", uniqid(rand(), true));
        // Create hash
        $passwordHash = hash("sha512", $salt.$_POST['new_password']);

        $sql = "UPDATE users SET password = :password, salt = :salt WHERE userid = :userid";
        $req = $bdd->prepare($sql);
        $result = $req->execute(array(
            'userid' => $userid,
            'password' => $passwordHash,
            'salt' => $salt
        ));
        if($result) {
            $infos_arr[] = 'User password updated successfully.';
            $_SESSION['infos'] = $infos_arr;
        } else {
            $errors_arr[] = 'There was a problem in the SQL update of the password.';
            $_SESSION['errors'] = $errors_arr;
        }

        } else { // passwords do not match
            $errors_arr[] = 'Passwords do not match !';
            $_SESSION['errors'] = $errors_arr;
        }
    }

    $sql = "UPDATE users SET firstname = :firstname, lastname = :lastname, email = :email , is_admin = :is_admin, validated = :validated WHERE userid = :userid";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'firstname' => $firstname,
        'lastname' => $lastname,
        'email' => $email,
        'is_admin' => $is_admin,
        'validated' => $validated,
        'userid' => $userid
    ));
    if ($result){
        if(empty($errors_arr)) {
            $infos_arr[] = 'User infos updated successfully.';
            $_SESSION['infos'] = $infos_arr;
            header('Location: admin.php');
            exit();
        } else {
            header('Location: admin.php');
        }
    } else { //sql fail
        $errors_arr[] = 'There was a problem in the SQL request. Report a bug !';
        $_SESSION['errors'] = $errors_arr;
        header('Location: admin.php');
        exit();
    }
}

// ITEMS TYPES
if (isset($_POST['item_type_name']) && is_pos_int($_POST['item_type_id'])) {
    $item_type_id = $_POST['item_type_id'];
    $item_type_name = filter_var($_POST['item_type_name'], FILTER_SANITIZE_STRING); 
    // we remove the # of the hexacode and sanitize string
    $item_type_bgcolor = filter_var(substr($_POST['item_type_bgcolor'], 1, 6), FILTER_SANITIZE_STRING);
    $item_type_template = check_body($_POST['item_type_template']);
    //TODO
    $item_type_tags = '';
    $sql = "UPDATE items_types SET name = :name, bgcolor = :bgcolor , template = :template, tags = :tags WHERE id = :id";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'name' => $item_type_name,
        'bgcolor' => $item_type_bgcolor,
        'template' => $item_type_template,
        'tags' => $item_type_tags,
        'id' => $item_type_id
    ));
    if ($result){
        $infos_arr[] = 'New item category updated successfully.';
        $_SESSION['infos'] = $infos_arr;
        header('Location: admin.php#items_types');
        exit();
    } else { //sql fail
        $infos_arr[] = 'There was a problem in the SQL request. Report a bug !';
        $_SESSION['errors'] = $infos_arr;
        header('Location: admin.php');
        exit();
    }
}
// add new item type
if (isset($_POST['new_item_type']) && is_pos_int($_POST['new_item_type'])) {
    $item_type_name = filter_var($_POST['new_item_type_name'], FILTER_SANITIZE_STRING); 
    // we remove the # of the hexacode and sanitize string
    $item_type_bgcolor = filter_var(substr($_POST['new_item_type_bgcolor'], 1, 6), FILTER_SANITIZE_STRING);
    $item_type_template = check_body($_POST['new_item_type_template']);
    //TODO
    $item_type_tags = '';
    $sql = "INSERT INTO items_types(name, bgcolor, template, tags) VALUES(:name, :bgcolor, :template, :tags)";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'name' => $item_type_name,
        'bgcolor' => $item_type_bgcolor,
        'template' => $item_type_template,
        'tags' => $item_type_tags
    ));
    if ($result){
        $infos_arr[] = 'New item category added successfully.';
        $_SESSION['infos'] = $infos_arr;
        header('Location: admin.php#items_types');
        exit();
    } else { //sql fail
        $infos_arr[] = 'There was a problem in the SQL request. Report a bug !';
        $_SESSION['errors'] = $infos_arr;
        header('Location: admin.php');
        exit();
    }
}

