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

require_once('inc/check_files.php'); // Check uploaded FILES

// If input errors, redirect back to the team page
if($errflag) {
    $_SESSION['errors'] = $errmsg_arr;
    session_write_close();
    header("location: team.php");
    exit();
}
// END CHECK STUFF
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
    $sql = "INSERT INTO uploads(real_name, long_name, comment, userid, type) VALUES(:real_name, :long_name, :comment, :userid, :type)";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'real_name' => $real_filenames[$i],
        'long_name' => $long_filenames[$i],
        'comment' => $filecomments[$i],
        'userid' => $_SESSION['userid'],
        'type' => 'lm'
    ));
    $req->closeCursor();
            } // end for each file loop
        } // end if move uploaded
    } // end is uploaded
if($result) {
    header('location: team.php');
} else {
    echo "Something went wrong in the database query. Check the flux capacitor.";
}
