<?php
/******************************************************************************
*   Copyright 2012 Nicolas CARPi
*   This file is part of eLabFTW. 
*
*    eLabFTW is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    eLabFTW is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.
*
********************************************************************************/
require_once('inc/common.php');
// get $body from $_POST['body']
$body = check_body($_POST['body']);
// get $id from $_POST['id']
if(is_pos_int($_POST['id'])){
    $id = $_POST['id'];
} else {
    die('Bad id value.');
}

// SQL for editDB autosave
    $sql = "UPDATE items
        SET body = :body 
        WHERE userid = :userid 
        AND id = :id";
$req = $bdd->prepare($sql);
$result = $req->execute(array(
    'body' => $body,
    'userid' => $_SESSION['userid'],
    'id' => $id
));
