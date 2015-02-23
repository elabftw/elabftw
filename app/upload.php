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
/*  
    we receive the file in $_FILES['file']. The array looks like that :
    name : filename.pdf
    type : "application/pdf"
    tmp_name "/tmp/phpLzaurte"
    error : 0
    size 134482
 */

// check the item_id
if (is_pos_int($_GET['item_id'])) {
    $item_id = $_GET['item_id'];
} else {
    die('Bad ID');
}

// are we uploading for an experiment or a database item ?
if ($_GET['type'] === 'experiments' || $_GET['type'] === 'items') {
    $type = $_GET['type'];
} else {
    die('Bad type');
}

if ($type === 'experiments') {
    // we check that the user owns the experiment before adding thigs to it
    if (!is_owned_by_user($item_id, 'experiments', $_SESSION['userid'])) {
        die('Not your experiment');
    }
}

if (count($_FILES) === 0) {
    die('No files received');
}

// Create a clean filename : remplace all non letters/numbers by '.' (this way we don't lose the file extension)
$realname = preg_replace('/[^A-Za-z0-9]/', '.', $_FILES['file']['name']);
// get extension
$path_info = pathinfo($realname);
if (!empty($path_info['extension'])) {
    $ext = $path_info['extension'];
} else {
    $ext = "unknown";
}
// Create a unique long filename + extension
$longname = hash("sha512", uniqid(rand(), true)).".".$ext;
// Try to move the file to its final place
if (rename($_FILES['file']['tmp_name'], ELAB_ROOT.'uploads/'.$longname)) {

    // generate a md5sum of the file if it's not too big
    if ($_FILES['file']['size'] < 5000000) {
        $md5 = hash_file('md5', ELAB_ROOT.'uploads/' . $longname);
    } else {
        $md5 = null;
    }

    // SQL TO PUT FILE IN UPLOADS TABLE
    $sql = "INSERT INTO uploads(real_name, long_name, comment, item_id, userid, type, md5) VALUES(:real_name, :long_name, :comment, :item_id, :userid, :type, :md5)";
    $req = $pdo->prepare($sql);
    $result = $req->execute(array(
    'real_name' => $realname,
    'long_name' => $longname,
    // comment can be edited after upload
    // not i18n friendly because it is used somewhere else (not a valid reason, but for the moment that will do)
    'comment' => 'Click to add a comment',
    'item_id' => $item_id,
    'userid' => $_SESSION['userid'],
    'type' => $type,
    'md5' => $md5
    ));

}
