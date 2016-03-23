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

$creator = new \Elabftw\Elabftw\Create();

// Check ID
if (isset($_GET['id']) && !empty($_GET['id']) && Tools::checkId($_GET['id'])) {
    $id = $_GET['id'];
} else {
    die(_("The id parameter is not valid!"));
}

if ($_GET['type'] === 'exp') {
    $new_id = $creator->duplicateExperiment($_GET['id']);
} elseif ($_GET['type'] === 'db') {
    $new_id = $creator->duplicateItem($_GET['id']);
} else {
    die(_("The type parameter is not valid."));
}

if (Tools::checkId($new_id)) {
    if ($_GET['type'] === 'exp') {
        $msg_arr[] = _('Experiment successfully duplicated.');
        $_SESSION['ok'] = $msg_arr;
        header('location: ../experiments.php?mode=edit&id=' . $new_id . '');
        exit;
    } else {
        $msg_arr[] = _('Database entry successfully duplicated.');
        $_SESSION['ok'] = $msg_arr;
        header('location: ../database.php?mode=edit&id=' . $new_id . '');
        exit;
    }
} else {
    $msg_arr[] = sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug."), "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
    $_SESSION['ko'] = $msg_arr;
    header('location: ../experiments.php');
    exit;
}
