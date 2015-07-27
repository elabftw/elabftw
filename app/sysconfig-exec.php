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
/* sysconfig-exec.php - for the sysadmin */
require_once '../inc/common.php';

// only sysadmin can use this
if (($_SESSION['is_sysadmin'] != 1) || ($_SERVER['REQUEST_METHOD'] != 'POST')) {
    die(_('This section is out of your reach.'));
}

$sysconfig = new \Elabftw\Elabftw\SysConfig($db);

$msg_arr = array();
$errflag = false;
$tab = '';

// ADD A NEW TEAM
if (isset($_POST['new_team']) &&
    $_POST['new_team'] != '' &&
    $_POST['new_team'] != ' ') {

    $new_team_name = filter_var($_POST['new_team'], FILTER_SANITIZE_STRING);

    if ($sysconfig->addTeam($new_team_name)) {
        $msg_arr[] = _('Team added successfully.');
        $_SESSION['infos'] = $msg_arr;
        header('Location: ../sysconfig.php');
        exit;
    } else {
        $errflag = true;
        $errnum = '5';
        $msg_arr[] = sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug.") . "<br>E#5", "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
        $_SESSION['errors'] = $msg_arr;
        header('Location: ../sysconfig.php');
        exit;
    }
}
