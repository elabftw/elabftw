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
// lucky.php
require_once('inc/common.php');
if (isset($_GET['find']) && !empty($_GET['find'])) {
    $find = filter_var($_GET['find'], FILTER_SANITIZE_STRING);
    // LUCKY SQL
$sql = "SELECT * FROM experiments WHERE userid = ".$_SESSION['userid']." AND (title LIKE '%$find%' OR date LIKE '%$find%' OR body LIKE '%$find%') LIMIT 1";
$req = $bdd->prepare($sql);
$req->execute();
$count = $req->rowCount();
if ($count > 0) {
    $data = $req->fetch();
    header('Location: experiments.php?mode=view&id='.$data['id']);
    } else {
        $msg_arr = array();
        $msg_arr[] = "Nothing found with this query :/";
        $_SESSION['infos'] = $msg_arr;
        header('Location: search.php');
    }
}
?>
