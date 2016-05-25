<?php
/**
 * sysconfig-exec.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Deal with requests from sysconfig.php
 */
try {
    require_once '../inc/common.php';

    // only sysadmin can use this
    if ($_SESSION['is_sysadmin'] != 1 || $_SERVER['REQUEST_METHOD'] != 'POST') {
        throw new Exception(_('This section is out of your reach.'));
    }

    $crypto = new CryptoWrapper();

    $tab = '1';


    // TAB 2 : SERVER
    if (isset($_POST['lang'])) {
        $tab = '2';

        if (isset($_POST['lang']) && (strlen($_POST['lang']) === 5)) {
            $lang = $_POST['lang'];
        } else {
            $lang = 'en_GB';
        }
        if (isset($_POST['proxy'])) {
            $proxy = filter_var($_POST['proxy'], FILTER_SANITIZE_STRING);
        } else {
            $proxy = '';
        }
        // SQL
        $updates = array(
            'lang' => $lang,
            'proxy' => $proxy
        );
        if (!update_config($updates)) {
            throw new Exception('Error updating config');
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
            throw new Exception('Error updating config');
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
            throw new Exception('Error updating config');
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
        if (isset($_POST['smtp_port']) && Tools::checkId($_POST['smtp_port'])) {
            $smtp_port = $_POST['smtp_port'];
        } else {
            $smtp_port = '';
        }
        if (isset($_POST['smtp_username'])) {
            $smtp_username = filter_var($_POST['smtp_username'], FILTER_SANITIZE_STRING);
        } else {
            $smtp_username = '';
        }
        if (isset($_POST['smtp_password']) && !empty($_POST['smtp_password'])) {
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
            throw new Exception('Error updating config');
        }

    } // END EMAIL

    $_SESSION['ok'][] = _('Configuration updated successfully.');

} catch (Exception $e) {
    $_SESSION['ko'][] = Tools::error();
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());

} finally {
    header('Location: ../sysconfig.php?tab=' . $tab);
}
