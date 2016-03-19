<?php
/**
 * app/admin-ajax.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Deal with ajax requests sent from the admin page
 *
 */
require_once '../inc/common.php';

// the constructor will check for admin rights
try {
    $teamGroups = new \Elabftw\Elabftw\TeamGroups();
} catch (Exception $e) {
    die($e->getMessage());
}

// CREATE TEAM GROUP
if (isset($_POST['create_teamgroup'])) {
    try {
        $teamGroups->create(filter_var($_POST['create_teamgroup'], FILTER_SANITIZE_STRING));
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}

// EDIT TEAM GROUP NAME FROM JEDITABLE
if (isset($_POST['update_teamgroup'])) {
    try {
        // the output is echoed so it gets back into jeditable input field
        echo $teamGroups->update(filter_var($_POST['update_teamgroup'], FILTER_SANITIZE_STRING), $_POST['id']);
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}

// ADD OR REMOVE USER TO/FROM TEAM GROUP
if (isset($_POST['teamgroup_user'])) {
    try {
        $teamGroups->updateMember($_POST['teamgroup_user'], $_POST['teamgroup_group'], $_POST['action']);
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}

// DESTROY TEAM GROUP
if (isset($_POST['destroy_teamgroup'])) {
    try {
        $teamGroups->destroy($_POST['teamgroup_group']);
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}
// DEFAULT EXPERIMENT TEMPLATE
if (isset($_POST['commonTplUpdate'])) {
    try {
        $admin = new \Elabftw\Elabftw\Admin();
        $admin->commonTplUpdate($_POST['commonTplUpdate']);
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}

