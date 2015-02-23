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
// formkey stuff
//require_once 'inc/classes/formkey.class.php';
//$formKey = new formKey();

//Array to store validation errors
$msg_arr = array();
//Validation error flag
$errflag = false;

// CHECKS
/*
// Check the form_key
if (!isset($_POST['form_key']) || !$formKey->validate()) {
    // form key is invalid
    $msg_arr[] = 'The form key is invalid !';
    $errflag = true;
}
 */
// ID
if (is_pos_int($_POST['item_id'])) {
    $id = $_POST['item_id'];
} else {
    $id='';
    $msg_arr[] = _("The id parameter is not valid!");
    $errflag = true;
}
$title = check_title($_POST['title']);
$date = check_date($_POST['date']);
$body = check_body($_POST['body']);

// Store stuff in Session to get it back if error input
$_SESSION['new_title'] = $title;
$_SESSION['new_date'] = $date;

// If input errors, redirect back to the experiment form
if ($errflag) {
    $_SESSION['errors'] = $msg_arr;
    session_write_close();
    header("location: ../experiments.php?mode=show&id=".$id);
    exit;
}

// SQL for editXP
    $sql = "UPDATE experiments 
        SET title = :title, 
        date = :date, 
        body = :body
        WHERE userid = :userid 
        AND id = :id";
$req = $pdo->prepare($sql);
$result = $req->execute(array(
    'title' => $title,
    'date' => $date,
    'body' => $body,
    'userid' => $_SESSION['userid'],
    'id' => $id
));

// we add a revision to the revision table
$sql = "INSERT INTO experiments_revisions (exp_id, body, userid) VALUES(:exp_id, :body, :userid)";
$req = $pdo->prepare($sql);
$result = $req->execute(array(
'exp_id' => $id,
'body' => $body,
'userid' => $_SESSION['userid']
));


// Check if insertion is successful
if ($result) {
    unset($_SESSION['new_title']);
    unset($_SESSION['new_date']);
    unset($_SESSION['errors']);
    header("location: ../experiments.php?mode=view&id=".$id);
    exit;
} else {
    die(sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug."), "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>"));
}
