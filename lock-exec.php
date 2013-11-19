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
// lock-exec.php
require_once('inc/common.php');
// Check id is valid and assign it to $id
if (isset($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
} else {
    die("The id parameter in the URL isn't a valid ID");
}

// what do we do ? lock or unlock ?
if (isset($_GET['action']) && ($_GET['action'] == 'lock')) {
    $action = 1; // lock
} else {
    $action = 0; // unlock
}

// is it an experiment or a database item ?
if (isset($_GET['type']) && ($_GET['type'] == 'experiments')) {
    $sql = "UPDATE experiments SET locked = :action WHERE id = :id AND userid = :userid";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'action' => $action,
        'id' => $id,
        'userid' => $_SESSION['userid']
    ));
    if ($result) {
        header("Location: experiments.php?mode=view&id=$id");
    } else {
        die('SQL failed');
    }
} else { // we are updating a database item (no userid here)
    $sql = "UPDATE items SET locked = :action WHERE id = :id";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'action' => $action,
        'id' => $id
    ));
    if ($result) {
        header("Location: database.php?mode=view&id=$id");
    } else {
        die('SQL failed');
    }
}

