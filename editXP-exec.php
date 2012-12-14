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
require_once('inc/common.php');

//Array to store validation errors
$msg_arr = array();
//Validation error flag
$errflag = false;

// CHECKS
// ID
if(is_pos_int($_POST['item_id'])){
    $id = $_POST['item_id'];
} else {
    $id='';
    $msg_arr[] = 'The id parameter is not valid !';
    $errflag = true;
}
require_once('inc/check_title.php'); // $title
require_once('inc/check_date.php'); // $date
$body = check_body($_POST['body']);
require_once('inc/check_outcome.php'); // $outcome
require_once('inc/check_files.php'); // $real_filenames[] $long_filenames[]

// Store stuff in Session to get it back if error input
$_SESSION['new_title'] = $title;
$_SESSION['new_date'] = $date;
$_SESSION['new_outcome'] = $outcome;

// If input errors, redirect back to the experiment form
if($errflag) {
    $_SESSION['errors'] = $msg_arr;
    session_write_close();
    header("location: experiments.php?mode=show&id=$id");
    exit();
}

// SQL for editXP
    $sql = "UPDATE experiments 
        SET title = :title, 
        date = :date, 
        body = :body, 
        outcome = :outcome
        WHERE userid = :userid 
        AND id = :id";
$req = $bdd->prepare($sql);
$result = $req->execute(array(
    'title' => $title,
    'date' => $date,
    'body' => $body,
    'outcome' => $outcome,
    'userid' => $_SESSION['userid'],
    'id' => $id
));

// If FILES are uploaded
if (is_uploaded_file($_FILES['files']['tmp_name'][0])){
    // Assign the experiment id to $expid
        $item_id = $id;
        // Loop for each file
        for ($i = 0; $i < $cnt; $i++) {
        // Comments
    $filecomments[] = filter_var($_POST['filescom'][$i], FILTER_SANITIZE_STRING);
    if(strlen($filecomments[$i]) == 0){
        $filecomments[$i] = 'No comment added';
    }
        // Move file
            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $upload_directory . $long_filenames[$i])) {
    //SQL for FILE uploads
    $sql = "INSERT INTO uploads(real_name, long_name, comment, item_id, userid, type) VALUES(:real_name, :long_name, :comment, :item_id, :userid, :type)";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'real_name' => $real_filenames[$i],
        'long_name' => $long_filenames[$i],
        'comment' => $filecomments[$i],
        'item_id' => $item_id,
        'userid' => $_SESSION['userid'],
        'type' => 'experiments'
    ));
    $req->closeCursor();
            } // end for each file loop
        } // end if move uploaded
    } // end is uploaded

// Check if insertion is successful
if($result) {
    unset($_SESSION['new_title']);
    unset($_SESSION['new_date']);
    unset($_SESSION['outcome']);
    unset($_SESSION['errors']);
    header("location: experiments.php?mode=view&id=$id");
} else {
    die('Something went wrong in the database query. Check the flux capacitor.');
}
?>
