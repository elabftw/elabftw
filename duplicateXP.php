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
require_once("inc/auth.php");
require_once("inc/functions.php");
require_once("inc/connect.php");


// Get experiment id we duplicate
$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

// SQL to get data from the experiment we duplicate
$sql = "SELECT title, body FROM experiments WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// Assign variables
$title = $data['title'];
$body = $data['body'];
// Put today's date
$date = kdate();
// Default is running when duplicating an experiment
$outcome = 'running';

// SQL for duplicateXP
$sql = "INSERT INTO experiments(title, date, body, outcome, userid) VALUES(:title, :date, :body, :outcome, :userid)";
$req = $bdd->prepare($sql);
$result = $req->execute(array(
    'title' => $title,
    'date' => $date,
    'body' => $body,
    'outcome' => $outcome,
    'userid' => $_SESSION['userid']));
// END SQL main


// Get what is the experiment id we just created
$sql = "SELECT id FROM experiments WHERE userid = :userid ORDER BY id DESC LIMIT 0,1";
$req = $bdd->prepare($sql);
$req->bindParam(':userid', $_SESSION['userid']);
$req->execute();
$data = $req->fetch();
$newid = $data['id'];

// TAGS
// Get the tags. here $id is the expid we duplicated
$sql = "SELECT tag FROM experiments_tags WHERE item_id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
while($tags = $req->fetch()){
    // Put them in the new one. here $newid is the new exp created
    $sql = "INSERT INTO experiments_tags(tag, item_id, userid) VALUES(:tag, :item_id, :userid)";
    $reqtag = $bdd->prepare($sql);
    $reqtag->execute(array(
        'tag' => $tags['tag'],
        'item_id' => $newid,
        'userid' => $_SESSION['userid']
    ));
}

// Check if insertion is successful and redirect to the newly created experiment in edit mode
if($result) {
// info box
$msg_arr = array();
$msg_arr[] = 'Experiment successfully duplicated';
$_SESSION['infos'] = $msg_arr;
    header('location: experiments.php?mode=edit&id='.$newid.'');
} else {
    echo "Something went wrong in the database query. Check the flux capacitor.";
}
?>
