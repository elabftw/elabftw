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
namespace Elabftw\Elabftw;

require_once '../inc/common.php';

$title = Tools::checkTitle($_POST['title']);

$body = Tools::checkBody($_POST['body']);

$date = Tools::kdate($_POST['date']);

if ($_POST['type'] == 'experiments') {

    $Experiments = new Experiments($_SESSION['userid'], $_POST['id']);
    $result = $Experiments->update($title, $date, $body);

} elseif ($_POST['type'] == 'items') {

    $Database = new Database($_SESSION['team_id'], $_POST['id']);
    $result = $Database->update($title, $date, $body, $_SESSION['userid']);
}

if ($result) {
    echo 1;
} else {
    echo 0;
}
