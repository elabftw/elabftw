<?php
/**
 * app/controllers/TeamGroupsController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

/**
 * Deal with ajax requests sent from the admin page
 *
 */
require_once '../../inc/common.php';

// the constructor will check for admin rights
try {
    $teamGroups = new TeamGroups();
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
            echo $teamGroups->update(
                filter_var($_POST['teamGroupUpdateName'], FILTER_SANITIZE_STRING),
                $_POST['id'],
                $_SESSION['team_id']
            );
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

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
}
