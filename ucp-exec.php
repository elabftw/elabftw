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
require_once 'inc/common.php';
require_once 'lang/'.$_SESSION['prefs']['lang'].'.php';

// INFO BOX
$msg_arr = array();
$errflag = false;
$infoflag = false;
$email = '';
$username = '';
$firstname = '';
$lastname = '';
$website = '';


// Form 1 User infos
if (isset($_POST['currpass'])){
    // 1. Check that we were given a good password
    // Get salt
    $sql = "SELECT salt FROM users WHERE userid = :userid LIMIT 1";
    $salt = $pdo->prepare($sql);
    $salt->bindParam(':userid', $_SESSION['userid']);
    $salt->execute();
    $salt = $salt->fetchColumn();
    // Create hash
    $passwordHash = hash("sha512", $salt.$_POST['currpass']);
    $sql = "SELECT userid FROM users WHERE userid = :userid AND password = :password LIMIT 1";
    $req = $pdo->prepare($sql);
    $result = $req->execute(array(
        'userid' => $_SESSION['userid'],
        'password' => $passwordHash));
    $numrows = $req->rowCount();
    if( ($result) && ($numrows === 1) ) {
        // Old password is good. Continue

        // PASSWORD CHANGE
        if ((isset($_POST['cnewpass'])) && (!empty($_POST['cnewpass']))) {
            $cpassword = filter_var($_POST['cnewpass'], FILTER_SANITIZE_STRING);
            if ((isset($_POST['newpass'])) && (!empty($_POST['newpass']))) {
                // Good to go
                $password = filter_var($_POST['newpass'], FILTER_SANITIZE_STRING);
                // Check for password length
                if (strlen($password) <= 7) {
                    $msg_arr[] = PASSWORD_TOO_SHORT;
                    $errflag = true;
                }
                if (strcmp($password, $cpassword) != 0 ) {
                    $msg_arr[] = PASSWORD_DONT_MATCH;
                    $errflag = true;
                }
                // Create salt
                $salt = hash("sha512", uniqid(rand(), true));
                // Create hash
                $passwordHash = hash("sha512", $salt.$password);
                $sql = "UPDATE users SET salt = :salt, 
                    password = :password 
                    WHERE userid = :userid";
                $req = $pdo->prepare($sql);
                $result = $req->execute(array(
                    'salt' => $salt,
                    'password' => $passwordHash,
                    'userid' => $_SESSION['userid']));
                if($result){
                    $msg_arr[] = PASSWORD_SUCCESS;
                    $infoflag = true;
                } else {
                    die(ERROR_BUG);
                }
            }
        }
        // Check USERNAME (sanitize and validate)
            if ((isset($_POST['username'])) && (!empty($_POST['username']))) {
            $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
            // Check for duplicate username in DB
            $sql = "SELECT * FROM users WHERE username='$username'";
            $result = $pdo->query($sql);
            $numrows = $result->rowCount();
            $data = $result->fetch();
            if($result) {
                if($numrows > 0) {
                    if($data['userid'] != $_SESSION['userid']){
                    $msg_arr[] = REGISTER_USERNAME_USED;
                    $errflag = true;
                }
            }
            }
        } else {
            $msg_arr[] = FIELD_MISSING;
            $errflag = true;
        }
        // Check FIRSTNAME (sanitize only)
            if ((isset($_POST['firstname'])) && (!empty($_POST['firstname']))) {
            $firstname = filter_var($_POST['firstname'], FILTER_SANITIZE_STRING);
        } else {
            $msg_arr[] = FIELD_MISSING;
            $errflag = true;
        }
        // Check LASTNAME (sanitize only)
            if ((isset($_POST['lastname'])) && (!empty($_POST['lastname']))) {
            $lastname = filter_var($_POST['lastname'], FILTER_SANITIZE_STRING);
        } else {
            $msg_arr[] = FIELD_MISSING;
            $errflag = true;
        }

        // Check EMAIL (sanitize and validate)
        if ((isset($_POST['email'])) && (!empty($_POST['email']))) {
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $msg_arr[] = INVALID_EMAIL;
                $errflag = true;
            } else {
                // Check for duplicate email in DB
                $sql = "SELECT * FROM users WHERE email='$email'";
                $result = $pdo->query($sql);
                $numrows = $result->rowCount(); 
                $data = $result->fetch();
                if($result) {
                    if($numrows > 0) {
                        if($data['userid'] != $_SESSION['userid']){
                        $msg_arr[] = REGISTER_EMAIL_USED;
                        $errflag = true;
                        }
                    }
                }
            }
        } else {
            $msg_arr[] = FIELD_MISSING;
            $errflag = true;
        }
        // Check phone
        if (isset($_POST['phone']) && !empty($_POST['phone'])) {
            $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
        } else {
            $phone = null;
        }
        // Check cellphone
        if (isset($_POST['cellphone']) && !empty($_POST['cellphone'])) {
            $cellphone = filter_var($_POST['cellphone'], FILTER_SANITIZE_STRING);
        } else {
            $cellphone = null;
        }
        // Check skype
        if (isset($_POST['skype']) && !empty($_POST['skype'])) {
            $skype = filter_var($_POST['skype'], FILTER_SANITIZE_STRING);
        } else {
            $skype = null;
        }
        // Check website
        if (isset($_POST['website']) && !empty($_POST['website'])) {
            if  (filter_var($_POST['website'], FILTER_VALIDATE_URL)) {
                $website = $_POST['website'];
            } else { // do not validate as url
                $msg_arr[] = FIELD_MISSING;
                $errflag = true;
            }
        } else {
            $website = null;
        }

        //If there are input validations, redirect back to the registration form
        if($errflag) {
            $_SESSION['errors'] = $msg_arr;
            session_write_close();
            header("location: ucp.php");
            exit;
        }

        // SQL for update profile
        $sql = "UPDATE users SET salt = :salt, 
            password = :password,
            email = :email,
            username = :username,
            firstname = :firstname,
            lastname = :lastname,
            phone = :phone,
            cellphone = :cellphone,
            skype = :skype,
            website = :website
            WHERE userid = :userid";
        $req = $pdo->prepare($sql);
        $result = $req->execute(array(
            'salt' => $salt,
            'password' => $passwordHash,
            'email' => $email,
            'username' => $username,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'phone' => $phone,
            'cellphone' => $cellphone,
            'skype' => $skype,
            'website' => $website,
            'userid' => $_SESSION['userid']));
        if($result){
            $msg_arr[] = UCP_PROFILE_UPDATED;
            $infoflag = true;
        } else {
            die(ERROR_BUG);
        }
    }else{ //end if result and numrow > 1
        $msg_arr[] = UCP_ENTER_PASSWORD;
        $errflag = true;
    }
}// end if first form submitted

// FORM 2. PREFERENCES
if (isset($_POST['display'])) {
    if ($_POST['display'] === 'default'){
        $new_display = 'default';
    } elseif ($_POST['display'] === 'compact'){
        $new_display = 'compact';
    } else {
        die(ERROR_BUG);
    }

    // ORDER
    if ($_POST['order'] === 'date' || $_POST['order'] === 'id' || $_POST['order'] === 'title') {
        $new_order = $_POST['order'];
    } else {
        die(ERROR_BUG);
    }

    // SORT
    if ($_POST['sort'] === 'asc') {
        $new_sort = $_POST['sort'];
    } elseif ($_POST['sort'] === 'desc') {
        $new_sort = $_POST['sort'];
    } else {
        die(ERROR_BUG);
    }

    // LIMIT
    $filter_options = array(
        'options' => array(
            'default' => 15,
            'min_range' => 1,
            'max_range' => 500
        ));
    $new_limit = filter_var($_POST['limit'], FILTER_VALIDATE_INT, $filter_options);

    // KEYBOARD SHORTCUTS
    $new_sc_create = substr($_POST['create'], 0, 1);
    $new_sc_edit = substr($_POST['edit'], 0, 1);
    $new_sc_submit = substr($_POST['submit'], 0, 1);
    $new_sc_todo = substr($_POST['todo'], 0, 1);

    // CLOSE WARNING
    if (isset($_POST['close_warning']) && $_POST['close_warning'] === 'on') {
        $new_close_warning = 1;
    } else {
        $new_close_warning = 0;
    }

    // LANG
    $lang_array = array('en-GB', 'fr-FR', 'pt-BR');
    if (isset($_POST['lang']) && in_array($_POST['lang'], $lang_array)) {
        $new_lang = $_POST['lang'];
    } else {
        $new_lang = 'en-GB';
    }


    // SQL
    $sql = "UPDATE users SET
        display = :new_display,
        order_by = :new_order,
        sort_by = :new_sort,
        limit_nb = :new_limit,
        sc_create = :new_sc_create,
        sc_edit = :new_sc_edit,
        sc_submit = :new_sc_submit,
        sc_todo = :new_sc_todo,
        close_warning = :new_close_warning,
        lang = :new_lang
        WHERE userid = :userid;";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'new_display' => $new_display,
        'new_order' => $new_order,
        'new_sort' => $new_sort,
        'new_limit' => $new_limit,
        'new_sc_create' => $new_sc_create,
        'new_sc_edit' => $new_sc_edit,
        'new_sc_submit' => $new_sc_submit,
        'new_sc_todo' => $new_sc_todo,
        'new_close_warning' => $new_close_warning,
        'new_lang' => $new_lang,
        'userid' => $_SESSION['userid']
    ));
    // put it in session
    $_SESSION['prefs']['display'] = $new_display;
    $_SESSION['prefs']['order'] = $new_order;
    $_SESSION['prefs']['sort'] = $new_sort;
    $_SESSION['prefs']['limit'] = $new_limit;
    $_SESSION['prefs']['shortcuts']['create'] = $new_sc_create;
    $_SESSION['prefs']['shortcuts']['edit'] = $new_sc_edit;
    $_SESSION['prefs']['shortcuts']['submit'] = $new_sc_submit;
    $_SESSION['prefs']['shortcuts']['todo'] = $new_sc_todo;
    $_SESSION['prefs']['close_warning'] = $new_close_warning;
    $_SESSION['prefs']['lang'] = $new_lang;
    $msg_arr[] = UCP_PREFS_UPDATED;
    $infoflag = true;
}

// EXPERIMENTS TEMPLATES
// add new tpl
if (isset($_POST['new_tpl_form'])) {
    // do nothing if the template name is empty
    if (empty($_POST['new_tpl_name'])) {
        $msg_arr[] = UCP_TPL_NAME;
        $errflag = true;
    // template name must be 3 chars at least
    } elseif (strlen($_POST['new_tpl_name']) < 3) {
        $msg_arr[] = UCP_TPL_SHORT;
        $errflag = true;
    } else {
        $tpl_name = filter_var($_POST['new_tpl_name'], FILTER_SANITIZE_STRING);
        $tpl_body = check_body($_POST['new_tpl_body']);
        $sql = "INSERT INTO experiments_templates(team, name, body, userid) VALUES(:team, :name, :body, :userid)";
        $req = $pdo->prepare($sql);
        $result = $req->execute(array(
            'team' => $_SESSION['team_id'],
            'name' => $tpl_name,
            'body' => $tpl_body,
            'userid' => $_SESSION['userid']
        ));
        $msg_arr[] = UCP_TPL_SUCCESS;
        $infoflag = true;
    }
}

// edit templates
if (isset($_POST['tpl_form'])) {
    $tpl_id = array();
    foreach ($_POST['tpl_id'] as $id) {
        $tpl_id[] = $id;
    }
    $new_tpl_body = array();
    foreach ($_POST['tpl_body'] as $body) {
        $new_tpl_body[] = $body;
    }
    $new_tpl_name = array();
    foreach ($_POST['tpl_name'] as $name) {
        $new_tpl_name[] = $name;
    }
    $new_tpl_body[] = filter_var($_POST['tpl_body'], FILTER_SANITIZE_STRING); 
    $new_tpl_name[] = filter_var($_POST['tpl_name'], FILTER_SANITIZE_STRING); 
    $sql = "UPDATE experiments_templates SET body = :body, name = :name WHERE userid = ".$_SESSION['userid']." AND id = :id";
    $req = $pdo->prepare($sql);
    for ($i = 0; $i < count($_POST['tpl_body']); $i++) {
    $req->execute(array(
        'id' => $tpl_id[$i],
        'body' => $new_tpl_body[$i],
        'name' => $new_tpl_name[$i]
    ));
    }
    $msg_arr[] = UCP_TPL_EDITED;
    $infoflag = true;
}


// INFO BOX
if ($errflag) {
    $_SESSION['errors'] = $msg_arr;
    header("location: ucp.php");
    exit;
} elseif ($infoflag) {
    $_SESSION['infos'] = $msg_arr;
    header("location: ucp.php");
    exit;
} else {
    header("location: ucp.php");
    exit;
}
