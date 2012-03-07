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
*    the License, or (at your option) any eLabFTWlater version.                 *
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
require_once('inc/common.php');

//////////////
// This page increments the value of journal and last_jc for the $_SESSION['S_jcnb'] members
//////////////

// Check the inc=1 GET value and assign it to $inc
if((isset($_GET['inc'])) && (filter_var($_GET['inc'], FILTER_VALIDATE_INT)) && ($_GET['inc'] === '1')) {

    // Only member of journalclub group can do that
    $sql = "SELECT * FROM users WHERE userid = ".$_SESSION['userid'];
    $req = $bdd->query($sql);
    $data = $req->fetch();
    $req->closeCursor();
    if(($data['group'] === 'journalclub') || ($data['group'] === 'admin')) {
        // Who will be incremented ?
        $req = $bdd->query("SELECT * FROM `users` ORDER BY `users`.`last_jc` ASC LIMIT 0 , ".$_SESSION['S_jcnb']."");
        // Get the time to increment it
        $curr_time = date("ymd"); // 110929
        // Actually increment values of journal
        while ($data = $req->fetch()) {
        $bdd->exec("UPDATE `users` SET journal=journal+1, last_jc=".$curr_time." WHERE `userid` = ". $data['userid'] ."");
        }
        header("location: team.php#jc");
    }else {
        die("You are not responsable of the journal clubs !");
    }
} else {
    header("location: team.php");
}
?>
