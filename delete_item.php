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
    die("The id parameter in the URL isn't a valid item ID");
}

// Item switch
if (isset($_GET['type']) && ($_GET['type'] === 'exp')){
    $item_type = 'experiments';
} else {
    $item_type = 'items';
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
// DELETE ITEM
$sql = "DELETE FROM ".$item_type." WHERE id=".$id;
$req = $bdd->prepare($sql);
$result = $req->execute();

// DELETE TAGS
$sql = "DELETE FROM ".$item_type."_tags WHERE item_id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();

// DELETE FILES
$sql = "DELETE FROM uploads WHERE item_id = :id AND type = :type";
$req = $bdd->prepare($sql);
if($item_type === 'experiments'){
$req->execute(array(
    'id' => $id,
    'type' => 'exp' 
));
}
if($item_type === 'items'){
$req->execute(array(
    'id' => $id,
    'type' => 'database' 
));
}
// TODO set Null for exp with linked item

// TODO check that the 3 sql went OK
if ($result) {
    $msg_arr = array();
    if ($item_type === 'experiments'){
        $msg_arr[] = 'Experiment deleted successfully';
    } else {
        $msg_arr[] = 'Item deleted successfully';
    }

    $_SESSION['infos'] = $msg_arr;
    if ($item_type === 'experiments') {
        header("location: experiments.php");
    } elseif ($item_type === 'items') {
        header("location: database.php");
    } else {
        header("location: ucp.php");
    }
} else { // no $result
    die('Something went wrong in the database query. Check the flux capacitor.');
}
?>
