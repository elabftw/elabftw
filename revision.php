<?php
/**
 * revision.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Show history of body of experiment or db item
 *
 */
require_once 'inc/common.php';
$page_title = _('Revisions');
$selected_menu = null;
$errflag = false;

// CHECKS
if (isset($_GET['item_id']) &&
    !empty($_GET['item_id']) &&
    is_pos_int($_GET['item_id'])) {

    $id = $_GET['item_id'];
} else {
    $errflag = true;
    $msg_arr[] = _("Invalid ID!");
}

$whitelist_arr = array('experiments', 'items');

if (isset($_GET['type']) &&
    !empty($_GET['type']) &&
    in_array($_GET['type'], $whitelist_arr)) {
        $type = $_GET['type'];
        $location = 'experiments';
} else {
    $errflag = true;
    $msg_arr[] = _("Bad type!");
}

if ($type === 'items') {
    $location = 'database';
    // check item is in team
    if (!item_is_in_team($id, $_SESSION['team_id'])) {
        $msg_arr[] = _('This section is out of your reach.');
        $errflag = true;
    }
} else {
    // check we own the experiment
    if (!is_owned_by_user($id, 'experiments', $_SESSION['userid'])) {
        $msg_arr[] = _('This section is out of your reach.');
        $errflag = true;
    }
}

// THE RESTORE ACTION
if (isset($_GET['action']) && $_GET['action'] === 'restore' && is_pos_int($_GET['rev_id'])) {
    // we don't update if the item is locked
    $sql = "SELECT locked FROM " . $type . " WHERE id = :id";
    $req = $pdo->prepare($sql);
    $req->bindParam(':id', $id, PDO::PARAM_INT);
    $req->execute();
    $locked = $req->fetch();
    if ($locked['locked'] == 1) {
        $msg_arr = _('You cannot restore a revision of a locked item!');
        $errflag = true;
    }

    if (!$errflag) {
        // get the body of the restored time
        $sql = "SELECT body FROM " . $type . "_revisions WHERE id = :rev_id";
        $req = $pdo->prepare($sql);
        $req->bindParam(':rev_id', $_GET['rev_id'], PDO::PARAM_INT);
        $req->execute();
        $revision = $req->fetch();

        // if there is no error, restore the thing
        $sql = "UPDATE " . $type . " SET body = :body WHERE id = :id";
        $req = $pdo->prepare($sql);
        $req->bindParam(':body', $revision['body']);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->execute();

        header("Location: " . $location . ".php?mode=view&id=$id");
        exit;
    }
}


if (!$errflag) {
    require_once 'inc/head.php';
    echo "<a href='" . $location . ".php?mode=view&id=" . $id . "'><h4><img src='img/undo.png' alt='<--' /> " . _('Go back') . "</h4></a>";


    // Get the currently stored body
    $sql = "SELECT * FROM " . $type . " WHERE id = :id";
    $req = $pdo->prepare($sql);
    $req->bindParam(':id', $id, PDO::PARAM_INT);
    $req->execute();
    $current = $req->fetch();
    echo "<div class='item'>" . _('Current:') . "<br>" . $current['body'] . "</div>";

    // Get list of revisions
    $sql = "SELECT * FROM " . $type . "_revisions WHERE item_id = :item_id AND userid = :userid ORDER BY savedate DESC";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'item_id' => $id,
        'userid' => $_SESSION['userid']
    ));
    while ($revisions = $req->fetch()) {
        echo "<div class='item'>" . _('Saved on:') . " " . $revisions['savedate'] . " <a href='revision.php?item_id=" . $id . "&type=" . $type . "&action=restore&rev_id=" . $revisions['id'] . "'>" . _('Restore') . "</a><br>";
        echo $revisions['body'] . "</div>";
    }

    require_once 'inc/footer.php';
} else {
    $_SESSION['ko'] = $msg_arr;
    header('Location: experiments.php');
}
