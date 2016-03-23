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
$users = new \Elabftw\Elabftw\Users();

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
            $_SESSION['ko'] = $msg_arr;
            header('location: ../admin.php');
            exit;
        }
    }
    $_SESSION['ok'] = $msg_arr;
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

// REDIRECT USER
if ($errflag) {
    $msg_arr[] = sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug.") . "<br>E#" . $error, "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
    $_SESSION['ko'] = $msg_arr;
    header('Location: ../admin.php?tab=' . $tab);
} else {
    $msg_arr[] = _('Configuration updated successfully.');
    $_SESSION['ok'] = $msg_arr;
    header('Location: ../admin.php?tab=' . $tab);
}
