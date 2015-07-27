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
$msg_arr = array();

$creator = new \Elabftw\Elabftw\Create($db);


// What do we create ?
if (isset($_GET['type']) && !empty($_GET['type']) && is_pos_int($_GET['type'])) {
    // $type is int for DB items
    $new_id = $creator->createItem($_GET['type']);

} elseif (isset($_GET['type']) && !empty($_GET['type']) && ($_GET['type'] === 'exp')) {
    if (isset($_GET['tpl']) && !empty($_GET['tpl'])) {
        $new_id = $creator->createExperiment($_GET['tpl']);
    } else {
        $new_id = $creator->createExperiment();
    }


} else {
    $msg_arr[] = _('Wrong item type!');
    $_SESSION['infos'] = $msg_arr;
    header('location: ../index.php');
    exit;
}


// Check if insertion is successful and redirect to the newly created experiment/item in edit mode
if (is_pos_int($new_id)) {
    // info box
    $msg_arr[] = _('New item created successfully.');
    $_SESSION['infos'] = $msg_arr;
    if ($_GET['type'] === 'exp') {
        header('location: ../experiments.php?mode=edit&id=' . $new_id . '');
        exit;
    } else {
        header('location: ../database.php?mode=edit&id=' . $new_id . '');
        exit;
    }
} else {
        $msg_arr[] = sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug.") . "<br>E#17", "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
        header('location: ../experiments.php');
        exit;
}
