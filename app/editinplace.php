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

if (isset($_POST['filecomment'])) {
    // we are editing a comment for a file
    // there is never a New comment to INSERT because by default there is 'Click to add a comment' shown
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // post['id'] looks like comment_56
        $id_arr = explode('_', $_POST['id']);
        if (is_pos_int($id_arr[1])) {
            $id = $id_arr[1];
            // Update comment
            if (($_POST['filecomment'] != '') && ($_POST['filecomment'] != ' ')) {
                $filecomment = filter_var($_POST['filecomment'], FILTER_SANITIZE_STRING);
                // SQL to update single file comment
                $sql = "UPDATE uploads SET comment = :new_comment WHERE id = :id";
                $req = $pdo->prepare($sql);
                $req->execute(array(
                    'new_comment' => $filecomment,
                    'id' => $id));
            } else { // Submitted comment is empty
                // Get old comment
                $sql = "SELECT comment FROM uploads WHERE id = " . $id;
                $req = $pdo->prepare($sql);
                $req->execute();
                $filecomment = $req->fetch();
            }
        }
    }



} elseif (isset($_POST['expcomment'])) {
// we are editing a comment on an xp
    // Check ID
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // either we have something that looks like comment_56 and we need to UPDATE
        // or we have new_expcomment and we need to INSERT
        $id_arr = explode('_', $_POST['id']);
        // NEW COMMENT FOR EXPERIMENT
        if ($id_arr[0] === 'newexpcomment' && is_pos_int($id_arr[1])) {
            // the $id here is the one of the experiment, not the comment.
            $id = $id_arr[1];
            $expcomment = filter_var($_POST['expcomment'], FILTER_SANITIZE_STRING);
            // SQL to insert expcomment
            $sql = "INSERT INTO experiments_comments(datetime, exp_id, comment, userid) 
                VALUES(:datetime, :exp_id, :comment, :userid)";
            $req = $pdo->prepare($sql);
            $req->execute(array(
                'datetime' => date("Y-m-d H:i:s"),
                'exp_id' => $id, // the $id here is the one of the experiment
                'comment' => $expcomment,
                'userid' => $_SESSION['userid']
            ));
        } else {
            // UPDATE OF EXISTING COMMENT
            if ($id_arr[0] === 'expcomment' && is_pos_int($id_arr[1])) {
                $id = $id_arr[1];
                // Update comment
                if (($_POST['expcomment'] != '') && ($_POST['expcomment'] != ' ')) {

                    // we must first check
                    $expcomment = filter_var($_POST['expcomment'], FILTER_SANITIZE_STRING);
                    // SQL to update single exp comment
                    $sql = "UPDATE experiments_comments SET 
                        comment = :new_comment, 
                        datetime = :now 
                        WHERE id = :id";
                    $req = $pdo->prepare($sql);
                    $req->execute(array(
                        'new_comment' => $expcomment,
                        'now' => date("Y-m-d H:i:s"),
                        'id' => $id
                    ));
                } else { // Submitted comment is empty
                    // Get old comment
                    $sql = "SELECT comment FROM experiments_comments WHERE id = :id";
                    $req = $pdo->prepare($sql);
                    $req->execute(array(
                        'id' => $id
                    ));
                    $comment = $req->fetch();
                }
            }
        }
    }
} else {
    die('Wrong comment_type');
}
