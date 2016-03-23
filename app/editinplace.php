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
namespace Elabftw\Elabftw;

require_once '../inc/common.php';

if (isset($_POST['filecomment'])) {
    // we are editing a comment for a file
    // there is never a New comment to INSERT because by default there is 'Click to add a comment' shown
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // post['id'] looks like comment_56
        $id_arr = explode('_', $_POST['id']);
        if (Tools::checkId($id_arr[1])) {
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
                echo stripslashes($filecomment);
            } else { // Submitted comment is empty
                // Get old comment
                $sql = "SELECT comment FROM uploads WHERE id = :id";
                $req = $pdo->prepare($sql);
                $req->bindParam(':id', $id);
                $req->execute();
                $filecomment = $req->fetch();
                echo stripslashes($filecomment['comment']);
            }
        }
    }
}
