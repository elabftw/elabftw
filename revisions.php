<?php
/**
 * revisions.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \Exception;

/**
 * Show history of body of experiment or db item
 *
 */
require_once 'inc/common.php';
$page_title = _('Revisions');
$selected_menu = null;
$errflag = false;
require_once 'inc/head.php';


try {
    if ($_GET['type'] === 'experiments') {

        // only experiment owner can change or see revisions
        if (!is_owned_by_user($_GET['item_id'], 'experiments', $_SESSION['userid'])) {
            throw new Exception(_('This section is out of your reach.'));
        }
        $location = 'experiments';

    } elseif ($_GET['type'] === 'items') {

        // check if item is in team
        $Database = new Database($_SESSION['team_id'], $_GET['item_id']);
        if (!$Database->isInTeam()) {
            throw new Exception(_('This section is out of your reach.'));
        }
        $location = 'database';

    } else {
        throw new Exception('Bad type!');
    }

    $Revisions = new Revisions($_GET['type'], $_GET['item_id']);

    // THE RESTORE ACTION
    if (isset($_GET['action']) && $_GET['action'] === 'restore') {

        $Revisions->restore($_GET['rev_id']);

        header("Location: " . $location . ".php?mode=view&id=" . $_GET['item_id'] . "");
        exit;
    }

    // BEGIN PAGE
    echo "<a href='" . $location . ".php?mode=view&id=" . $_GET['item_id'] . "'><h4><img src='img/undo.png' alt='<--' /> " . _('Go back') . "</h4></a>";
    $revisionArr = $Revisions->read();
    foreach ($revisionArr as $revision) {
        echo "<div class='item'>" . _('Saved on:') . " " . $revision['savedate'] . " <a href='revisions.php?item_id=" . $_GET['item_id'] . "&type=" . $_GET['type'] . "&action=restore&rev_id=" . $revision['id'] . "'>" . _('Restore') . "</a><br>";
        echo $revision['body'] . "</div>";
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
    display_message('ko', $e->getMessage());
} finally {
    require_once 'inc/footer.php';
}
