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
/* sysconfig-exec.php - for the sysadmin */
require_once '../inc/common.php';

// only sysadmin can use this
if ($_SESSION['is_sysadmin'] != 1 || $_SERVER['REQUEST_METHOD'] != 'POST') {
    die(_('This section is out of your reach.'));
}

$crypto = new \Elabftw\Elabftw\CryptoWrapper();

$msg_arr = array();
$errflag = false;
$tab = '1';


// TAB 2 : SERVER
if (isset($_POST['lang'])) {
    $tab = '2';

    if (isset($_POST['lang']) && (strlen($_POST['lang']) === 5)) {
        $lang = $_POST['lang'];
    } else {
        $lang = 'en_GB';
    }
    if ($_POST['debug'] == 1) {
        $debug = 1;
    } else {
        $debug = 0;
    }
    if (isset($_POST['proxy'])) {
        $proxy = filter_var($_POST['proxy'], FILTER_SANITIZE_STRING);
    } else {
        $proxy = '';
    }
    // SQL
    $updates = array(
        'lang' => $lang,
        'debug' => $debug,
        'proxy' => $proxy
    );
    if (!update_config($updates)) {
        $errflag = true;
        $error = '6';
    }
}
// END TAB 2

// TAB 3 : TIMESTAMP
if (isset($_POST['stampshare'])) {
    $tab = '3';
    $post_stamp = processTimestampPost();

    $updates = array(
        'stampprovider' => $post_stamp['stampprovider'],
        'stampcert' => $post_stamp['stampcert'],
        'stampshare' => $post_stamp['stampshare'],
        'stamplogin' => $post_stamp['stamplogin'],
        'stamppass' => $post_stamp['stamppass']
    );

    if (!update_config($updates)) {
        $errflag = true;
        $error = '7';
    }
} // END TAB 3

// TAB 4 : SECURITY
if (isset($_POST['admin_validate'])) {
    $tab = '4';

    if ($_POST['admin_validate'] == 1) {
        $admin_validate = 1;
    } else {
        $admin_validate = 0;
    }
    if (isset($_POST['login_tries'])) {
        $login_tries = filter_var($_POST['login_tries'], FILTER_SANITIZE_STRING);
    } else {
        $login_tries = '3';
    }
    if (isset($_POST['ban_time'])) {
        $ban_time = filter_var($_POST['ban_time'], FILTER_SANITIZE_STRING);
    } else {
        $ban_time = '30';
    }

    $updates = array(
        'admin_validate' => $admin_validate,
        'login_tries' => $login_tries,
        'ban_time' => $ban_time
    );

    if (!update_config($updates)) {
        $errflag = true;
        $error = '8';
    }
} // END TAB 4

// TAB 5 : EMAIL
if (isset($_POST['mail_method'])) {
    $tab = '5';

    // Whitelist for valid mailing methods
    $valid_mail_methods = array('smtp', 'php', 'sendmail');

    // Check if POST variable for mail_method is white-listed
    if (in_array($_POST['mail_method'], $valid_mail_methods)) {
        $mail_method = $_POST['mail_method'];
    // if not, fall back to sendmail method
    } else {
        $mail_method = 'sendmail';
    }

    if (isset($_POST['sendmail_path'])) {
        $sendmail_path = filter_var($_POST['sendmail_path'], FILTER_SANITIZE_STRING);
    } else {
        $sendmail_path = '';
    }
    if (isset($_POST['mail_from'])) {
        $mail_from = filter_var($_POST['mail_from'], FILTER_SANITIZE_EMAIL);
    } else {
        $mail_from = '';
    }
    if (isset($_POST['smtp_address'])) {
        $smtp_address = filter_var($_POST['smtp_address'], FILTER_SANITIZE_STRING);
    } else {
        $smtp_address = '';
    }
    if (isset($_POST['smtp_encryption'])) {
        $smtp_encryption = filter_var($_POST['smtp_encryption'], FILTER_SANITIZE_STRING);
    } else {
        $smtp_encryption = '';
    }
    if (isset($_POST['smtp_port']) && is_pos_int($_POST['smtp_port'])) {
        $smtp_port = $_POST['smtp_port'];
    } else {
        $smtp_port = '';
    }
    if (isset($_POST['smtp_username'])) {
        $smtp_username = filter_var($_POST['smtp_username'], FILTER_SANITIZE_STRING);
    } else {
        $smtp_username = '';
    }
    if (isset($_POST['smtp_password'])) {
        // the password is stored encrypted in the database
        $smtp_password = $crypto->encrypt($_POST['smtp_password']);
    } else {
        $smtp_password = '';
    }

    $updates = array(
        'smtp_address' => $smtp_address,
        'smtp_encryption' => $smtp_encryption,
        'smtp_port' => $smtp_port,
        'smtp_username' => $smtp_username,
        'smtp_password' => $smtp_password,
        'mail_method' => $mail_method,
        'mail_from' => $mail_from,
        'sendmail_path' => $sendmail_path
    );

    if (!update_config($updates)) {
        $errflag = true;
        $error = '9';
    }

} // END EMAIL

// REDIRECT USER
if ($errflag) {
    $msg_arr[] = sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug.") . "<br>E#" . $error, "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
    $_SESSION['errors'] = $msg_arr;
    header('Location: ../sysconfig.php?tab=' . $tab);
} else {
    $msg_arr[] = _('Configuration updated successfully.');
    $_SESSION['infos'] = $msg_arr;
    header('Location: ../sysconfig.php?tab=' . $tab);
}
