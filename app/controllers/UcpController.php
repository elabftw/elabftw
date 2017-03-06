<?php
/**
 * app/controllers/UcpController.php
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
 * Deal with ajax requests sent from the user control panel
 *
 */
try {
    require_once '../../app/init.inc.php';

    $tab = 1;
    $redirect = false;

    // TAB 1 : PREFERENCES
    if (isset($_POST['display'])) {
        $redirect = true;
        if ($Users->updatePreferences($_POST)) {
            $_SESSION['ok'][] = _('Preferences updated.');
        } else {
            $_SESSION['ko'][] = Tools::error();
        }
    }
    // END TAB 1

    // TAB 2 : ACCOUNT
    if (isset($_POST['currpass'])) {
        $tab = '2';
        $redirect = true;

        if ($Users->updateAccount($_POST)) {
            $_SESSION['ok'][] = _('Profile updated.');
        } else {
            $_SESSION['ko'][] = Tools::error();
        }
    }
    // END TAB 2

    // TAB 3 : EXPERIMENTS TEMPLATES

    // ADD NEW TPL
    if (isset($_POST['new_tpl_form'])) {
        $tab = '3';
        $redirect = true;

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

        $Templates = new Templates($Users);
        if (!$Templates->create($tpl_name, $tpl_body, $_SESSION['userid'])) {
            throw new Exception(Tools::error());
        }
        $_SESSION['ok'][] = _('Experiment template successfully added.');
    }

    // EDIT TEMPLATES
    if (isset($_POST['tpl_form'])) {
        $tab = '3';
        $redirect = true;

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

        $Templates = new Templates($Users);

        $cnt = count($_POST['tpl_body']);
        for ($i = 0; $i < $cnt; $i++) {
            $Templates->update($tpl_id[$i], $new_tpl_name[$i], $new_tpl_body[$i]);
        }
        $_SESSION['ok'][] = _('Template successfully edited.');
    }

    // TEMPLATES DESTROY
    if (isset($_POST['templatesDestroy'])) {
        if (Tools::checkId($_POST['id']) === false) {
            throw new Exception('The id parameter is invalid!');
        }

        $Templates = new Templates($Users);

        if ($Templates->destroy($_POST['id'], $_SESSION['userid'])) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Template deleted successfully')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
    $_SESSION['ko'][] = $e->getMessage();
} finally {
    if ($redirect) {
        header('Location: ../../ucp.php?tab=' . $tab);
    }
}
