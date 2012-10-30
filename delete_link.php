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
if (isset($_POST['id']) && is_pos_int($_POST['id'])) {
    $id = $_POST['id'];
} else {
    die("The id parameter in the URL isn't a valid link ID");
}
// Check item_id is valid and assign it to $item_id
if(isset($_POST['item_id']) && is_pos_int($_POST['item_id'])) {
    $item_id = $_POST['item_id'];
} else {
    die("The item id parameter in the URL isn't valid !");
}

// Check item_id is owned by connected user
$sql = "SELECT userid FROM experiments WHERE id = ".$item_id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();
if ($data['userid'] == $_SESSION['userid']){
   // SQL for DELETE TAG
    $sql = "DELETE FROM experiments_links WHERE id=".$id;
    $req = $bdd->prepare($sql);
    $result = $req->execute();
    if(!$result) {
        die('Something went wrong in the database query. Check the flux capacitor.');
    }
    header("Location: experiments.php?mode=edit&id='.$item_id.'");
}
