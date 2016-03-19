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
    $itemsTypes = new \Elabftw\Elabftw\ItemsTypes();
    $teamGroups = new \Elabftw\Elabftw\TeamGroups();
    $commonTpl = new \Elabftw\Elabftw\CommonTpl();
    $status = new \Elabftw\Elabftw\Status();
} catch (Exception $e) {
    die($e->getMessage());
}

// CREATE STATUS
if (isset($_POST['statusCreate'])) {
    try {
        $status->create($_POST['statusName'], $_POST['statusColor'], $_SESSION['team_id']);
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}

// UPDATE STATUS
if (isset($_POST['statusUpdate'])) {
    try {
        $status->update(
            $_POST['statusId'],
            $_POST['statusName'],
            $_POST['statusColor'],
            $_POST['statusDefault'],
            $_SESSION['team_id']
        );
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}

// CREATE ITEMS TYPES
if (isset($_POST['itemsTypesCreate'])) {
    try {
        $itemsTypes->create(
            $_POST['itemsTypesName'],
            $_POST['itemsTypesColor'],
            $_POST['itemsTypesTemplate'],
            $_SESSION['team_id']
        );
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}

// UPDATE ITEM TYPE
if (isset($_POST['itemsTypesUpdate'])) {
    try {
        $itemsTypes->update(
            $_POST['itemsTypesId'],
            $_POST['itemsTypesName'],
            $_POST['itemsTypesColor'],
            $_POST['itemsTypesTemplate'],
            $_SESSION['team_id']
        );
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}

// CREATE TEAM GROUP
if (isset($_POST['teamGroupCreate'])) {
    try {
        $teamGroups->create(filter_var($_POST['teamGroupCreate'], FILTER_SANITIZE_STRING), $_SESSION['team_id']);
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}

// EDIT TEAM GROUP NAME FROM JEDITABLE
if (isset($_POST['teamGroupUpdateName'])) {
    try {
        // the output is echoed so it gets back into jeditable input field
        echo $teamGroups->update(filter_var($_POST['teamGroupUpdateName'], FILTER_SANITIZE_STRING), $_POST['id']);
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}

// ADD OR REMOVE USER TO/FROM TEAM GROUP
if (isset($_POST['teamGroupUser'])) {
    try {
        $teamGroups->updateMember($_POST['teamGroupUser'], $_POST['teamGroupGroup'], $_POST['action']);
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}

// DESTROY TEAM GROUP
if (isset($_POST['teamGroupDestroy'])) {
    try {
        $teamGroups->destroy($_POST['teamGroupGroup']);
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}
// DEFAULT EXPERIMENT TEMPLATE
if (isset($_POST['commonTplUpdate'])) {
    try {
        $commonTpl->commonTplUpdate($_POST['commonTplUpdate'], $_SESSION['team_id']);
    } catch (Exception $e) {
        dblog('Error', $_SESSION['userid'], $e->getMessage());
    }
}
