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
// formkey stuff
require_once('lib/classes/formkey.class.php');
$formKey = new formKey();
// for success messages
$infos_arr = array();
// for error messages
$errors_arr = array();
$errflag = false;

// VALIDATE USERS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['validate'])) {
    // sql to validate users
    $sql = "UPDATE users SET validated = 1 WHERE userid = :userid";
    $req = $bdd->prepare($sql);
    // sql to get email of the user
    $sql_email = "SELECT email FROM users WHERE userid = :userid";
    $req_email = $bdd->prepare($sql_email);
    foreach ($_POST['validate'] as $user) {
        $req->execute(array(
            'userid' => $user
        ));
            $infos_arr[] = 'Validated user with user ID : '.$user;
        $req_email->execute(array(
            'userid' => $user
        ));
        $user = $req_email->fetch();
        // now let's get the URL so we can have a nice link in the email
        $url = 'https://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF'];
        $url = str_replace('admin-exec.php', 'login.php', $url);
        // we send an email to each validated new user
        require_once('lib/swift_required.php');
        // Create the message
        $message = Swift_Message::newInstance()
        // Give the message a subject
        ->setSubject('[eLabFTW] New user registred')
        // Set the From address with an associative array
        ->setFrom(array('elabftw.net@gmail.com' => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($user['email'] => 'Your account has been activated.'))
        // Give it a body
        ->setBody('Hi,
Your account on eLabFTW has been activated. You can now login:
'.$url.'

Thanks for using eLabFTW :)

~~
Email sent by eLabFTW
http://www.elabftw.net
Free open-source Lab Manager');
    $transport = Swift_SmtpTransport::newInstance(
        get_config('smtp_address'),
        get_config('smtp_port'),
        get_config('smtp_encryption'))
        ->setUsername(get_config('smtp_username'))
        ->setPassword(get_config('smtp_password'));
    $mailer = Swift_Mailer::newInstance($transport);
    $mailer->send($message);
    }
    $_SESSION['infos'] = $infos_arr;
    header('Location: admin.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['lab_name'])) {
    // MAIN CONFIGURATION FORM
    if (isset($_POST['lab_name'])) {
        $lab_name = filter_var($_POST['lab_name'], FILTER_SANITIZE_STRING);
    }
    if($_POST['admin_validate'] == 1) {
        $admin_validate = 1;
    } else {
        $admin_validate = 0;
    }
    if($_POST['deletable_xp'] == 1) {
        $deletable_xp = 1;
    } else {
        $deletable_xp = 0;
    }
    if($_POST['debug'] == 1) {
        $debug = 1;
    } else {
        $debug = 0;
    }
    if (isset($_POST['link_name'])) {
        $link_name = filter_var($_POST['link_name'], FILTER_SANITIZE_STRING);
    }
    if (isset($_POST['link_href'])) {
        $link_href = filter_var($_POST['link_href'], FILTER_SANITIZE_STRING);
    }
    if (isset($_POST['path'])) {
        $path = filter_var($_POST['path'], FILTER_SANITIZE_STRING);
    }
    if (isset($_POST['proxy'])) {
        $proxy = filter_var($_POST['proxy'], FILTER_SANITIZE_STRING);
    }
    if (isset($_POST['smtp_address'])) {
        $smtp_address = filter_var($_POST['smtp_address'], FILTER_SANITIZE_STRING);
    }
    if (isset($_POST['smtp_encryption'])) {
        $smtp_encryption = filter_var($_POST['smtp_encryption'], FILTER_SANITIZE_STRING);
    }
    if (isset($_POST['smtp_port']) && is_pos_int($_POST['smtp_port'])) {
        $smtp_port = $_POST['smtp_port'];
    }
    if (isset($_POST['smtp_username'])) {
        $smtp_username = filter_var($_POST['smtp_username'], FILTER_SANITIZE_STRING);
    }
    if (isset($_POST['smtp_password'])) {
        $smtp_password = filter_var($_POST['smtp_password'], FILTER_SANITIZE_STRING);
    }

    // build request array
    $updates = array(
        'lab_name' => $lab_name,
        'admin_validate' => $admin_validate,
        'deletable_xp' => $deletable_xp,
        'debug' => $debug,
        'link_name' => $link_name,
        'link_href' => $link_href,
        'path' => $path,
        'proxy' => $proxy,
        'smtp_address' => $smtp_address,
        'smtp_encryption' => $smtp_encryption,
        'smtp_port' => $smtp_port,
        'smtp_username' => $smtp_username,
        'smtp_password' => $smtp_password
    );
    $values = array();
    foreach ($updates as $name => $value) {
        $sql = "UPDATE config SET conf_value = '".$value."' WHERE conf_name = '".$name."';";
        $req = $bdd->prepare($sql);
        $result = $req->execute();
    }
    if ($result){
        $infos_arr[] = 'Configuration updated successfully.';
        $_SESSION['infos'] = $infos_arr;
        header('Location: admin.php');
        exit();
    } else {
        $errors_arr[] = 'There was a problem in the SQL request. Report a bug !';
        $_SESSION['errors'] = $errors_arr;
        header('Location: admin.php');
    }
}

// EDIT USER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['userid'])) {
    if (!is_pos_int($_POST['userid'])) {
        $msg_arr[] = 'Userid is not valid.';
        $errflag = true;
    }
    if ($errflag) {
        $_SESSION['errors'] = $msg_arr;
        header("location: admin.php");
        die();
    }

    $userid = $_POST['userid'];
    // Put everything lowercase and first letter uppercase
    $firstname = ucwords(strtolower(filter_var($_POST['firstname'], FILTER_SANITIZE_STRING)));
    // Lastname in uppercase
    $lastname = strtoupper(filter_var($_POST['lastname'], FILTER_SANITIZE_STRING));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if($_POST['is_admin'] == 1) {
        $is_admin = 1;
    } else {
        $is_admin = 0;
    }
    if($_POST['can_lock'] == 1) {
        $can_lock = 1;
    } else {
        $can_lock = 0;
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

    $sql = "UPDATE users SET firstname = :firstname, lastname = :lastname, email = :email , is_admin = :is_admin, can_lock = :can_lock, validated = :validated WHERE userid = :userid";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'firstname' => $firstname,
        'lastname' => $lastname,
        'email' => $email,
        'is_admin' => $is_admin,
        'can_lock' => $can_lock,
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
    $sql = "INSERT INTO items_types(name, bgcolor, template) VALUES(:name, :bgcolor, :template)";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'name' => $item_type_name,
        'bgcolor' => $item_type_bgcolor,
        'template' => $item_type_template
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

// DELETE USER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    // Check the form_key
    if (!isset($_POST['form_key']) || !$formKey->validate()) {
        // form key is invalid
        $msg_arr[] = 'The form key is invalid !';
        $errflag = true;
    }
    if (filter_var($_POST['delete_user'], FILTER_VALIDATE_EMAIL)) {
        $email = $_POST['delete_user'];
    } else {
        $msg_arr[] = 'Email is not valid';
        $errflag = true;
    }
    if ($errflag) {
        $_SESSION['errors'] = $msg_arr;
        header("location: admin.php");
        die();
    }
    // look which user has this email address
    $sql = "SELECT userid FROM users WHERE email LIKE :email";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'email' => $email
    ));
    $user = $req->fetch();
    $userid = $user['userid'];

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
