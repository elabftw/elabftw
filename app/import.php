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
// it might take some time and we don't want to be cut in the middle, so set time_limit to ∞
set_time_limit(0);

$errflag = false;
$msg_arr = array();

try {
    switch ($_POST['type']) {
        case 'csv':
            $import = new \Elabftw\Elabftw\ImportCsv();
            break;

        case 'zip':
            $import = new \Elabftw\Elabftw\ImportZip();
            break;
        default:
            $errflag = true;
    }
} catch (Exception $e) {
    $errflag = true;
    $msg_arr[] = $e->getMessage();
}


// REDIRECT
if (!$errflag) {
    $msg_arr[] = $import->inserted . ' ' . ngettext('item imported successfully.', 'items imported successfully.', $import->inserted);
    $_SESSION['infos'] = $msg_arr;
    header('Location: ../database.php');
} else {
    $msg_arr[] = sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug.") . "<br>E#17", "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
    $_SESSION['errors'] = $msg_arr;
    header('Location: ../admin.php');
}
