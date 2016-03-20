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
require_once '../inc/common.php';

//Array to store validation errors
$msg_arr = array();
//Validation error flag
$errflag = false;

// CHECKS
// ID
if (is_pos_int($_POST['item_id'])) {
    $id = $_POST['item_id'];
    if (!item_is_in_team($id, $_SESSION['team_id'])) {
        die(_('This section is out of your reach.'));
    }
} else {
    $id = '';
    $msg_arr[] = _("The id parameter is not valid!");
    $errflag = true;
}
$title = check_title($_POST['title']);
$date = check_date($_POST['date']);
$body = check_body($_POST['body']);

if (!$errflag) {
    // SQL for editDB
        $sql = "UPDATE items 
            SET title = :title, 
            date = :date, 
            body = :body, 
            userid = :userid 
            WHERE id = :id";
    $req = $pdo->prepare($sql);
    $result1 = $req->execute(array(
        'title' => $title,
        'date' => $date,
        'body' => $body,
        'userid' => $_SESSION['userid'],
        'id' => $id
    ));

    // we add a revision to the revision table
    $sql = "INSERT INTO items_revisions (item_id, body, userid) VALUES(:item_id, :body, :userid)";
    $req = $pdo->prepare($sql);
    $result2 = $req->execute(array(
        'item_id' => $id,
        'body' => $body,
        'userid' => $_SESSION['userid']
    ));

    // Check if insertion is successful
    if ($result1 && $result2) {
        header("location: ../database.php?mode=view&id=" . $id);
    } else {
        $errflag = true;
        $msg_arr[] = "Error in the database!";
    }
}

// If input errors, redirect back to the edit form
if ($errflag) {
    $_SESSION['ko'] = $msg_arr;
    header("location: ../database.php?mode=edit&id=" . $id);
}
