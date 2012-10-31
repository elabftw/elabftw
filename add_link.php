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
/* addlink.php - for adding links */
require_once('inc/common.php');

// Check expid is valid and assign it to $item_id
if (isset($_POST['item_id']) && is_pos_int($_POST['item_id'])) {
    $item_id = $_POST['item_id'];
} else {
    die("The experiment id parameter in the URL isn't a valid experiment ID");
}
// Sanitize link
$link = filter_var($_POST['link'], FILTER_SANITIZE_STRING);
// Get the ID of this link item from title
$sql = "SELECT id FROM items WHERE title LIKE '$link'";
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();
if($req->rowcount() > 1){ die('I found several ID for this item title !'); }
if($req->rowcount() == 0){ die('Nope.'); }
print_r($data);
$link_id = $data['id'];

// Check expid is owned by connected user
$sql = "SELECT userid FROM experiments WHERE id = ".$item_id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();
if ($data['userid'] == $_SESSION['userid']) {
    // SQL for addlink
    $sql = "INSERT INTO experiments_links (item_id, link_id) VALUES(:item_id, :link_id)";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'item_id' => $item_id,
        'link_id' => $link_id
    ));
    if (!$result) {
        die('Something went wrong in the database query. Check the flux capacitor.');
    }
}
