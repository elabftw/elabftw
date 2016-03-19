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
require_once '../inc/common.php';

// only admin can use this
if ($_SESSION['is_admin'] != 1 || $_SERVER['REQUEST_METHOD'] != 'POST') {
    die(_('This section is out of your reach.'));
}

$formKey = new \Elabftw\Elabftw\FormKey();
$sysconfig = new \Elabftw\Elabftw\SysConfig();

$msg_arr = array();
$errflag = false;
$tab = '1';
$email = '';


// VALIDATE USERS
if (!empty($_POST['validate'])) {
    // sql to validate users
    $sql = "UPDATE users SET validated = 1 WHERE userid = :userid";
    $req = $pdo->prepare($sql);
    // check we only have int in validate array
    if (!filter_var_array($_POST['validate'], FILTER_VALIDATE_INT)) {
        die();
    }
    // sql to get email of the user
    $sql_email = "SELECT email FROM users WHERE userid = :userid";
    $req_email = $pdo->prepare($sql_email);
    // we loop the validate array
    foreach ($_POST['validate'] as $user) {
        // bind parameters of the user
        $req_email->bindParam(':userid', $user, PDO::PARAM_INT);
        $req->bindParam(':userid', $user, PDO::PARAM_INT);

        // validate the user
        $req->execute();
        $msg_arr[] = _('Validated user with ID :') . ' ' . $user;

        // get email
        $req_email->execute();
        $user = $req_email->fetch();
        // now let's get the URL so we can have a nice link in the email
        $url = 'https://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF'];
        $url = str_replace('app/admin-exec.php', 'login.php', $url);
        // we send an email to each validated new user
        $footer = "\n\n~~~\nSent from eLabFTW http://www.elabftw.net\n";
        // Create the message
        $message = Swift_Message::newInstance()
        // Give the message a subject
        // no i18n here
        ->setSubject('[eLabFTW] Account validated')
        // Set the From address with an associative array
        ->setFrom(array(get_config('mail_from') => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($user['email'] => 'eLabFTW'))
        // Give it a body
        ->setBody('Hello. Your account on eLabFTW was validated by an admin. Follow this link to login : ' . $url . $footer);
        // generate Swift_Mailer instance
            $mailer = getMailer();
        // now we try to send the email
        try {
            $mailer->send($message);
        } catch (Exception $e) {
            // log the error
            dblog('Error', $_SESSION['userid'], $e->getMessage());
            $errflag = true;
        }
        if ($errflag) {
            $msg_arr[] = _('There was a problem sending the email! Error was logged.');
            $_SESSION['errors'] = $msg_arr;
            header('location: ../admin.php');
            exit;
        }
    }
    $_SESSION['infos'] = $msg_arr;
    header('Location: ../admin.php');
    exit;
}
// END VALIDATE USERS

// TAB 1 : TEAM CONFIG
if (isset($_POST['deletable_xp'])) {
    $tab = '1';

    $post_stamp = processTimestampPost();

    // CHECKS
    if ($_POST['deletable_xp'] == 1) {
        $deletable_xp = 1;
    } else {
        $deletable_xp = 0;
    }
    if (isset($_POST['link_name'])) {
        $link_name = filter_var($_POST['link_name'], FILTER_SANITIZE_STRING);
    } else {
        $link_name = 'Documentation';
    }
    if (isset($_POST['link_href'])) {
        $link_href = filter_var($_POST['link_href'], FILTER_SANITIZE_STRING);
    } else {
        $link_href = 'doc/_build/html/';
    }

    $sql = "UPDATE teams SET
        deletable_xp = :deletable_xp,
        link_name = :link_name,
        link_href = :link_href,
        stamplogin = :stamplogin,
        stamppass = :stamppass,
        stampprovider = :stampprovider,
        stampcert = :stampcert
        WHERE team_id = :team_id";
    $req = $pdo->prepare($sql);
    $req->bindParam(':deletable_xp', $deletable_xp);
    $req->bindParam(':link_name', $link_name);
    $req->bindParam(':link_href', $link_href);
    $req->bindParam(':stamplogin', $post_stamp['stamplogin']);
    $req->bindParam(':stamppass', $post_stamp['stamppass']);
    $req->bindParam(':stampprovider', $post_stamp['stampprovider']);
    $req->bindParam(':stampcert', $post_stamp['stampcert']);
    $req->bindParam(':team_id', $_SESSION['team_id']);

    if (!$req->execute()) {
        $errflag = true;
        $error = '10';
    }
}

// TAB 2 : EDIT USERS
if (isset($_POST['userid'])) {
    $tab = '2';

    if (!is_pos_int($_POST['userid'])) {
        $error = _("Userid is not valid.");
        $errflag = true;
    }
    if ($errflag) {
        $_SESSION['errors'] = $msg_arr;
        header("location: ../admin.php?tab=" . $tab);
        exit;
    }

    $userid = $_POST['userid'];
    // Put everything lowercase and first letter uppercase
    $firstname = ucwords(strtolower(filter_var($_POST['firstname'], FILTER_SANITIZE_STRING)));
    // Lastname in uppercase
    $lastname = strtoupper(filter_var($_POST['lastname'], FILTER_SANITIZE_STRING));
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if ($_POST['validated'] == 1) {
        $validated = 1;
    } else {
        $validated = 0;
    }
    if (is_pos_int($_POST['usergroup'])) {
        // a non sysadmin cannot put someone sysadmin
        $usergroup = $_POST['usergroup'];
        if ($usergroup == 1 && $_SESSION['is_sysadmin'] != 1) {
            die(_('Only a sysadmin can put someone sysadmin.'));
        }

    } else {
        $usergroup = '4';
    }
    // reset password
    if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
        try {
            $user->updatePassword($_POST['new_password'], $userid);
        } catch (Exception $e) {
            $msg_arr[] = $e->getMessage();
            $errflag = true;
        }
    }

    $sql = "UPDATE users SET
        firstname = :firstname,
        lastname = :lastname,
        username = :username,
        email = :email,
        usergroup = :usergroup,
        validated = :validated
        WHERE userid = :userid";
    $req = $pdo->prepare($sql);
    $result = $req->execute(array(
        'firstname' => $firstname,
        'lastname' => $lastname,
        'username' => $username,
        'email' => $email,
        'usergroup' => $usergroup,
        'validated' => $validated,
        'userid' => $userid
    ));
    if (!$result) {
        $errflag = true;
        $error = '12';
    }
}

// DELETE USER (we receive a formkey from this form)
if (isset($_POST['delete_user']) && isset($_POST['delete_user_confpass'])) {
    // Check the form_key
    if (!isset($_POST['formkey']) || !$formKey->validate()) {
        // form key is invalid
        $msg_arr[] = _("Your session expired. Please retry.");
        $errflag = true;
    }
    // check the email is valid
    if (filter_var($_POST['delete_user'], FILTER_VALIDATE_EMAIL)) {
        $email = $_POST['delete_user'];
    } else {
        $msg_arr[] = _("The email is not valid.");
        $errflag = true;
    }
    // check that we got the good password
    if (!$user->checkCredentials($_SESSION['username'], filter_var($_POST['delete_user_confpass'], FILTER_SANITIZE_STRING))) {
        $msg_arr[] = _("Wrong password!");
        $errflag = true;
    }


    // here we store all the results of the different sql requests
    $result = array();
    // look which user has this email address and make sure it is in the same team as admin
    $sql = "SELECT userid FROM users WHERE email LIKE :email AND team = :team";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'email' => $email,
        'team' => $_SESSION['team_id']
    ));
    $user = $req->fetch();
    // email doesn't exist
    if ($req->rowCount() === 0) {
        $msg_arr[] = _('No user with this email or user not in your team');
        $errflag = true;
    }

    // Check for errors and redirect if there is one
    if ($errflag) {
        $_SESSION['errors'] = $msg_arr;
        header("location: ../admin.php");
        exit;
    }

    $userid = $user['userid'];
    // DELETE USER
    $sql = "DELETE FROM users WHERE userid = :userid";
    $req = $pdo->prepare($sql);
    $req->bindParam(':userid', $userid, PDO::PARAM_INT);
    $result[] = $req->execute();
    $sql = "DELETE FROM experiments_tags WHERE userid = :userid";
    $req = $pdo->prepare($sql);
    $req->bindParam(':userid', $userid, PDO::PARAM_INT);
    $result[] = $req->execute();
    $sql = "DELETE FROM experiments WHERE userid = :userid";
    $req = $pdo->prepare($sql);
    $req->bindParam(':userid', $userid, PDO::PARAM_INT);
    $result[] = $req->execute();
    // get all filenames
    $sql = "SELECT long_name FROM uploads WHERE userid = :userid AND type = :type";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'userid' => $userid,
        'type' => 'experiments'
    ));
    while ($uploads = $req->fetch()) {
        // Delete file
        $filepath = ELAB_ROOT . 'uploads/' . $uploads['long_name'];
        $result[] = unlink($filepath);
    }
    $sql = "DELETE FROM uploads WHERE userid = :userid";
    $req = $pdo->prepare($sql);
    $req->bindParam(':userid', $userid, PDO::PARAM_INT);
    $result[] = $req->execute();
    if (in_array(0, $result)) {
        $errflag = true;
        $error = '17';
    } else {
        $msg_arr[] = _('Everything was purged successfully.');
        $_SESSION['infos'] = $msg_arr;
        header('Location: ../admin.php?tab=' . $tab);
        exit;
    }
}

// REDIRECT USER
if ($errflag) {
    $msg_arr[] = sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug.") . "<br>E#" . $error, "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
    $_SESSION['errors'] = $msg_arr;
    header('Location: ../admin.php?tab=' . $tab);
} else {
    $msg_arr[] = _('Configuration updated successfully.');
    $_SESSION['infos'] = $msg_arr;
    header('Location: ../admin.php?tab=' . $tab);
}
