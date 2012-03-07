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
$errmsg_arr = array();
//Validation error flag
$errflag = false;


// CHECKS
require_once('inc/check_title.php'); // outputs $title
require_once('inc/check_date.php'); // outputs $date
require_once('inc/check_body.php'); // outputs $body
require_once('inc/check_outcome.php'); // outputs $outcome
require_once('inc/check_files.php'); // Check uploaded FILES
require_once('inc/check_protocol.php'); // outputs $prot_id


// Store stuff in Session to get it back if error input
$_SESSION['title'] = $title;
$_SESSION['date'] = $date;
$_SESSION['body'] = $body;
$_SESSION['outcome'] = $outcome;

// If input errors, redirect back to the experiment form
if($errflag) {
    $_SESSION['errors'] = $errmsg_arr;
    session_write_close();
    header("location: experiments.php?mode=create");
    exit();
}
// END CHECK STUFF

// SQL for createXP
$sql = "INSERT INTO experiments(title, date, body, outcome, protocol, userid) VALUES(:title, :date, :body, :outcome, :protocol, :userid)";
$req = $bdd->prepare($sql);
$result = $req->execute(array(
    'title' => $title,
    'date' => $date,
    'body' => $body,
    'outcome' => $outcome,
    'protocol' => $prot_id,
    'userid' => $_SESSION['userid']));
// END SQL main

// Get what is the experiment id we just created
$newid = $bdd->lastInsertId('id');

// FILES
// If FILES are uploaded
if (is_uploaded_file($_FILES['files']['tmp_name'][0])){
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
        'item_id' => $newid,
        'userid' => $_SESSION['userid'],
        'type' => 'exp'
    ));
    $req->closeCursor();
            } // end for each file loop
        } // end if move uploaded
    } // end is uploaded

// TAGS
$tags_arr = explode(" ", $_POST['tags']);
$sql = "INSERT INTO experiments_tags(tag, item_id, userid) VALUES(:tag, :item_id, :userid)";
$req = $bdd->prepare($sql);
foreach ($tags_arr as $tag){
$req->execute(array(
    'tag' => $tag,
    'item_id' => $newid,
    'userid' => $_SESSION['userid']
));
}

// Check if insertion is successful and redirect to the newly created experiment
if($result) {
    header('location: experiments.php?mode=view&id='.$newid.'');
} else {
    echo "Something went wrong in the database query. Check the flux capacitor.";
}
?>
