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
require_once('inc/auth.php');
require_once('inc/functions.php');
require_once('inc/functions.php');
//Array to store validation errors
$errmsg_arr = array();
//Validation error flag
$errflag = false;

// CHECKS
// outputs $title
require_once('inc/check_title.php');
// outputs $body
require_once('inc/check_body.php');
// Check uploaded FILES
require_once('inc/check_files.php');

// Store stuff in Session to get it back if error input
$_SESSION['title'] = $title;
$_SESSION['body'] = $body;

// If input errors, redirect back to the experiment form
if($errflag) {
    $_SESSION['errors'] = $errmsg_arr;
    session_write_close();
    header("location: protocols.php?mode=create");
    exit();
}
// END CHECK STUFF

// SQL for uploadPR
// Create date
$date = kdate();
$sql = "INSERT INTO protocols(title, date, body, userid) VALUES(:title, :date, :body, :userid)";
$req = $bdd->prepare($sql);
$result = $req->execute(array(
'title' => $title,
'date' => $date,
'body' => $body,
'userid' => $_SESSION['userid']
));

// Get what is the protocol id we just created
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
        'type' => 'prot'
    ));
    $req->closeCursor();
            } // end for each file loop
        } // end if move uploaded
} // end is uploaded

// TAGS
$tags_arr = explode(" ", $_POST['tags']);
$sql = "INSERT INTO protocols_tags(tag, item_id) VALUES(:tag, :item_id)";
$req = $bdd->prepare($sql);
foreach ($tags_arr as $tag){
$req->execute(array(
    'tag' => $tag,
    'item_id' => $newid
));
}

// Check if insertion is successful
if(!$result) {
echo "Something went wrong in the database query. Check the flux capacitor.";
} else {
header("location: protocols.php?mode=show");
}
?>
