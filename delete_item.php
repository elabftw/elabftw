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
// Check id is valid and assign it to $id
if(filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id = $_GET['id'];
} else {
    die("The id parameter in the URL isn't a valid experiment ID");
}

// Item switch
if ($_GET['type'] === 'exp'){
    $item_type = 'experiments';
} elseif ($_GET['type'] === 'prot'){
    $item_type = 'protocols';
} elseif ($_GET['type'] === 'tpl'){
    $item_type = 'experiments_templates';
} else {
    die('taggle');
}

// Check id is owned by connected user
if ($item_type === 'experiments' || $item_type === 'experiments_templates') {
    $sql = "SELECT userid FROM $item_type WHERE id = ".$id;
    $req = $bdd->prepare($sql);
    $req->execute();
    $result = $req->fetchColumn();
    if($result != $_SESSION['userid']) {
        die('You are trying to delete an item which is not yours !');
    }
}

// TODO if we delete a protocol, update the experiments linked to it with NULL
// SQL for delete_item.php
$sql = "DELETE FROM ".$item_type." WHERE id=".$id;
$req = $bdd->prepare($sql);
$result = $req->execute();

// Delete tags and files only for EXP and PROT
if ($item_type === 'experiments' || $item_type === 'protocols') {
// delete tags
$sql = "DELETE FROM ".$item_type."_tags WHERE item_id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
// delete files
$sql = "DELETE FROM uploads WHERE item_id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
}

// TODO check that the 3 sql went OK
if ($result) {
    $msg_arr = array();
    if ($item_type === 'experiments'){
        $msg_arr[] = 'Experiment deleted successfully';
    }elseif ($item_type === 'protocols'){
        $msg_arr[] = 'Protocol deleted successfully';
    } elseif ($item_type === 'experiments_templates') {
        $msg_arr[] = 'Template deleted successfully';
    } else {
        die('Bad type.');
    }

    $_SESSION['infos'] = $msg_arr;
    if ($item_type === 'experiments' || $item_type === 'protocols') {
        header("location: $item_type.php");
    } else {
        header("location: ucp.php");
    }
} else {
    die('Something went wrong in the database query. Check the flux capacitor.');
}
?>
