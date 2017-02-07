<?php
/**
 * ucp-exec.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Deal with post requests coming from ucp.php
 *
 */
require_once '../app/init.inc.php';

$tab = '1';

$Auth = new Auth();
$Users = new Users();

// TMP
$pdo = Db::getConnection();

try {
    // TAB 2 : ACCOUNT
    if (isset($_POST['currpass'])) {
        $tab = '2';

        // check that we got the good password
        $me = $Users->read($_SESSION['userid']);
        if (!$Auth->checkCredentials($me['email'], $_POST['currpass'])) {
            throw new Exception(_("Please input your current password!"));
        }

        if (!isset($_POST['firstname']) ||
            empty($_POST['firstname']) ||
            !isset($_POST['lastname']) ||
            empty($_POST['lastname']) ||
            !isset($_POST['email']) ||
            empty($_POST['email']) ||
            !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {

            throw new Exception(_('A mandatory field is missing!'));
        }

        $firstname = Tools::purifyFirstname($_POST['firstname']);
        $lastname = Tools::purifyLastname($_POST['lastname']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        if ($Users->isDuplicateEmail($email) && ($email != $me['email'])) {
            throw new Exception(_('Someone is already using that email address!'));
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
            if (filter_var($_POST['website'], FILTER_VALIDATE_URL)) {
                $website = $_POST['website'];
            } else { // does not validate as url
                throw new Exception(_('A mandatory field is missing!'));
            }
        } else {
            $website = null;
        }

        // PASSWORD CHANGE
        if (isset($_POST['cnewpass']) &&
            !empty($_POST['cnewpass']) &&
            isset($_POST['newpass']) &&
            !empty($_POST['newpass'])) {

            $password = $_POST['newpass'];
            $cpassword = $_POST['cnewpass'];

            // check confirmation password
            if (strcmp($password, $cpassword) != 0) {
                throw new Exception(_('The passwords do not match!'));
            }

            // UPDATE PASSWORD
            $Users->updatePassword($password);
        }

        // SQL for update preferences
        $sql = "UPDATE users SET
            email = :email,
            firstname = :firstname,
            lastname = :lastname,
            phone = :phone,
            cellphone = :cellphone,
            skype = :skype,
            website = :website
            WHERE userid = :userid";
        $req = $pdo->prepare($sql);
        $result = $req->execute(array(
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'phone' => $phone,
            'cellphone' => $cellphone,
            'skype' => $skype,
            'website' => $website,
            'userid' => $_SESSION['userid']));
        if (!$result) {
            throw new Exception(Tools::error());
        }

        $_SESSION['ok'][] = _('Profile updated.');

    }// END TAB 2

    // TAB 3 : EXPERIMENTS TEMPLATES
    // add new tpl
    if (isset($_POST['new_tpl_form'])) {
        $tab = '3';

        // do nothing if the template name is empty
        if (empty($_POST['new_tpl_name'])) {
            throw new Exception(_('You must specify a name for the template!'));
        }
        // template name must be 3 chars at least
        if (strlen($_POST['new_tpl_name']) < 3) {
            throw new Exception(_('The template name must be 3 characters long.'));
        }

        $tpl_name = filter_var($_POST['new_tpl_name'], FILTER_SANITIZE_STRING);
        $tpl_body = Tools::checkBody($_POST['new_tpl_body']);

        $Templates = new Templates($_SESSION['team_id']);
        if (!$Templates->create($tpl_name, $tpl_body, $_SESSION['userid'])) {
            throw new Exception(Tools::error());
        }
        $_SESSION['ok'][] = _('Experiment template successfully added.');
    }

    // edit templates
    if (isset($_POST['tpl_form'])) {
        $tab = '3';

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
        $sql = "UPDATE experiments_templates SET
            body = :body,
            name = :name
            WHERE userid = :userid AND id = :id";
        $req = $pdo->prepare($sql);
        $cnt = count($_POST['tpl_body']);
        for ($i = 0; $i < $cnt; $i++) {
            $req->execute(array(
                'id' => $tpl_id[$i],
                'body' => $new_tpl_body[$i],
                'name' => $new_tpl_name[$i],
                'userid' => $_SESSION['userid']
            ));
        }
        $_SESSION['ok'][] = _('Template successfully edited.');
    }

} catch (Exception $e) {
    $_SESSION['ko'][] = $e->getMessage();
} finally {
    header("location: ../ucp.php?tab=" . $tab);
}
