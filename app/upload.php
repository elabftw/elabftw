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
if (isset($_GET['item_id']) && is_pos_int($_GET['item_id'])) {
    $item_id = $_GET['item_id'];
}

// are we uploading for an experiment or a database item ?
$type_whitelist = array('experiments', 'items', 'zip_import');
if (in_array($_GET['type'], $type_whitelist)) {
    $type = $_GET['type'];
} else {
    die('Bad type');
}

if ($type === 'experiments') {
    // we check that the user owns the experiment before adding things to it
    if (!is_owned_by_user($item_id, 'experiments', $_SESSION['userid'])) {
        die('Not your experiment');
    }
}

// check we actually have files
if (count($_FILES) === 0) {
    die('No files received');
}

// UPLOAD A FILE TO AN EXPERIMENT OR DB ITEM
if ($type === 'experiments' || $type == 'items') {
    // Create a clean filename : remplace all non letters/numbers by '.' (this way we don't lose the file extension)
    $realname = preg_replace('/[^A-Za-z0-9]/', '.', $_FILES['file']['name']);

    // get extension
    $ext = \Elabftw\Elabftw\Tools::getExt($realname);

    // Create a unique long filename + extension
    $longname = hash("sha512", uniqid(rand(), true)) . "." . $ext;

    // Try to move the file to its final place
    if (rename($_FILES['file']['tmp_name'], ELAB_ROOT . 'uploads/' . $longname)) {

        // generate a sha256sum of the file if it's not too big
        // do that in chunks of (64 KB) to prevent using to much memory
        if ($_FILES['file']['size'] < 5000000) {
            $chunk_size = 65536;
            $fp = ELAB_ROOT . 'uploads/' . $longname;
            $ctx = hash_init('sha256');
            while (!feof($fp))
            {
                $buffer = fgets($fp, $chunk_size);
                hash_update($ctx, $buffer);
            }
            $hash = hash_final(ctx, true);
            $fclose($fp);
        } else {
            $hash = null;
        }

        // SQL TO PUT FILE IN UPLOADS TABLE
        $sql = "INSERT INTO uploads(
            real_name,
            long_name,
            comment,
            item_id,
            userid,
            type,
            hash,
            hash_algorithm
        ) VALUES(
            :real_name,
            :long_name,
            :comment,
            :item_id,
            :userid,
            :type,
            :hash,
            :hash_algorithm
        )";

        $req = $pdo->prepare($sql);
        $req->execute(array(
            'real_name' => $realname,
            'long_name' => $longname,
            // comment can be edited after upload
            // not i18n friendly because it is used somewhere else (not a valid reason, but for the moment that will do)
            'comment' => 'Click to add a comment',
            'item_id' => $item_id,
            'userid' => $_SESSION['userid'],
            'type' => $type,
            'hash' => $hash,
            'hash_algorithm' => 'sha256'
        ));
    } else {
        die('Cannot move the file.');
    }
}
