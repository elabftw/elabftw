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
require_once 'inc/common.php';
$page_title = REVISIONS_TITLE;
$selected_menu = null;
require_once 'inc/head.php';

if (isset($_GET['exp_id']) && !empty($_GET['exp_id']) && is_pos_int($_GET['exp_id'])) {
    $exp_id = $_GET['exp_id'];
} else {
    die(INVALID_ID);
}
echo "<a href='experiments.php?mode=view&id=".$exp_id."'><h4><img src='img/undo.png' alt='<--' /> ".REVISIONS_GO_BACK."</h4></a>";

if (isset($_GET['action']) && $_GET['action'] === 'restore' && is_pos_int($_GET['rev_id'])) {
    // get the body of the restored time
    $sql = "SELECT body FROM experiments_revisions WHERE id = :rev_id";
    $req = $pdo->prepare($sql);
    $req->bindParam(':rev_id', $_GET['rev_id'], PDO::PARAM_INT);
    $req->execute();
    $revision = $req->fetch();

    // we don't update if the experiment is locked
    // first check if it's locked
    $sql = "SELECT locked FROM experiments WHERE id = :exp_id";
    $req = $pdo->prepare($sql);
    $req->bindParam(':exp_id', $exp_id, PDO::PARAM_INT);
    $req->execute();
    $locked = $req->fetch();
    if ($locked['locked'] == 1) {
        display_message('error', REVISIONS_LOCKED);
        require_once 'inc/footer.php';
        exit;
    }
    
    // experiment is not locked, we can continue
    // sql to update the body of the experiment with the restored one
    $sql = "UPDATE experiments SET body = :body WHERE id = :exp_id";
    $req = $pdo->prepare($sql);
    $req->bindParam(':body', $revision['body']);
    $req->bindParam(':exp_id', $exp_id, PDO::PARAM_INT);
    $req->execute();
    header("Location:experiments.php?mode=view&id=$exp_id");
    exit;
}

// Get the currently stored body
$sql = "SELECT * FROM experiments
    WHERE id = :id";
$req = $pdo->prepare($sql);
$req->bindParam(':id', $exp_id, PDO::PARAM_INT);
$req->execute();
$experiment = $req->fetch();
echo "<div class='item'>".REVISIONS_CURRENT."<br>".$experiment['body']."</div>";

// Get list of revisions
$sql = "SELECT * FROM experiments_revisions WHERE exp_id = :exp_id AND userid = :userid ORDER BY savedate DESC";
$req = $pdo->prepare($sql);
$req->execute(array(
    'exp_id' => $exp_id,
    'userid' => $_SESSION['userid']
));
while($revisions = $req->fetch()) {
    echo "<div class='item'>".REVISIONS_SAVED." ".$revisions['savedate']." <a href='revision.php?exp_id=".$exp_id."&action=restore&rev_id=".$revisions['id']."'>".REVISIONS_RESTORE."</a><br>";
    echo $revisions['body']."</div>";
}
require_once 'inc/footer.php';
