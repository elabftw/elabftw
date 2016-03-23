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
use \Elabftw\Elabftw\Tools as Tools;

require_once '../inc/common.php';
// Check id is valid and assign it to $id
if (isset($_GET['id']) && Tools::checkId($_GET['id'])) {
    $id = $_GET['id'];
} else {
    die();
}

if ($_GET['type'] === 'experiments') {
// Check file id is owned by connected user
    $sql = "SELECT userid, real_name, long_name, item_id FROM uploads WHERE id = :id";
    $req = $pdo->prepare($sql);
    $req->bindParam(':id', $id, PDO::PARAM_INT);
    $req->execute();
    $data = $req->fetch();

    if ($data['userid'] == $_SESSION['userid']) {
        // Good to go -> delete file from SQL table
        $sql = "DELETE FROM uploads WHERE id = :id";
        $reqdel = $pdo->prepare($sql);
        $reqdel->bindParam(':id', $id, PDO::PARAM_INT);
        $reqdel->execute();

        // now delete it from filesystem
        $filepath = ELAB_ROOT . 'uploads/' . $data['long_name'];
        unlink($filepath);
        // remove thumbnail
        $ext = Tools::getExt($data['real_name']);
        $thumb_path = ELAB_ROOT . 'uploads/' . $data['long_name'] . '_th.jpg';
        if (file_exists($thumb_path)) {
            unlink($thumb_path);
        }
        // Redirect to the viewXP
        $msg_arr = array();
        $msg_arr [] = sprintf(_('File %s deleted successfully.'), $data['real_name']);
        $_SESSION['ok'] = $msg_arr;
        header("location: ../experiments.php?mode=edit&id=" . $data['item_id']);
    } else {
        die();
    }

// DATABASE ITEM
} elseif ($_GET['type'] === 'items') {
    // Get realname
    $sql = "SELECT real_name, long_name, item_id FROM uploads WHERE id = :id AND type = 'items'";
    $req = $pdo->prepare($sql);
    $req->bindParam(':id', $id, PDO::PARAM_INT);
    $req->execute();
    $data = $req->fetch();

    // Delete SQL entry (and verify that the type is database),
    // to avoid someone deleting files saying it's DB whereas it's exp
    $sql = "DELETE FROM uploads WHERE id = :id AND type = 'items'";
    $reqdel = $pdo->prepare($sql);
    $reqdel->bindParam(':id', $id, PDO::PARAM_INT);
    $reqdel->execute();

    // Delete file
    $filepath = ELAB_ROOT . 'uploads/' . $data['long_name'];
    unlink($filepath);

    // Redirect to the viewDB
    $msg_arr = array();
        $msg_arr [] = sprintf(_('File %s deleted successfully.'), $data['real_name']);
    $_SESSION['ok'] = $msg_arr;
    header("location: ../database.php?mode=edit&id=" . $data['item_id']);

} else {
    die();
}
