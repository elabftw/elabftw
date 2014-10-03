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
require_once 'inc/common.php';
require_once 'lang/'.$_SESSION['prefs']['lang'].'.php';
// Check ID
if (isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
} else {
    die(INVALID_ID);
}

if ($_GET['type'] === 'exp') {
    $type = 'experiments';
} elseif ($_GET['type'] === 'db') {
    $type = 'items';
} else {
    die(INVALID_TYPE);
}

// this function will return the ID of the new experiment
// or false if it failed somewhere
$newid = duplicate_item($id, $type);

if (is_pos_int($newid)) {
    if ($type === 'experiments') {
        $msg_arr[] = DUPLICATE_EXP_SUCCESS;
        $_SESSION['infos'] = $msg_arr;
        header('location: experiments.php?mode=edit&id='.$newid.'');
        exit;
    } else {
        $msg_arr[] = DUPLICATE_ITEM_SUCCESS;
        $_SESSION['infos'] = $msg_arr;
        header('location: database.php?mode=edit&id='.$newid.'');
        exit;
    }
} else {
    die(ERROR_BUG);
}
