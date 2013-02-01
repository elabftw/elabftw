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
require_once('inc/common.php');
// Check ID
if (isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
}

if ($_GET['type'] === 'exp'){
    $type = 'experiments';
} elseif ($_GET['type'] === 'pla') {
    $type = 'plasmids';
} else {
    die('Bad type.');
}

if ($type === 'experiments') {
    $elabid = generate_elabid();
    // SQL to get data from the experiment we duplicate
    $sql = "SELECT title, body FROM experiments WHERE id = ".$id;
    $req = $bdd->prepare($sql);
    $req->execute();
    $data = $req->fetch();
    // SQL for duplicateXP
    $sql = "INSERT INTO experiments(title, date, body, status, elabid, userid) VALUES(:title, :date, :body, :status, :elabid, :userid)";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'title' => $data['title'],
        'date' => kdate(),
        'body' => $data['body'],
        'status' => 'running',
        'elabid' => $elabid,
        'userid' => $_SESSION['userid']));
    // END SQL main


}

if ($type === 'plasmids') {
    // SQL to get data from the plasmid we duplicate
    $sql = "SELECT * FROM plasmids WHERE id = ".$id;
    $req = $bdd->prepare($sql);
    $req->execute();
    $data = $req->fetch();

    // SQL for duplicatePL
    $sql = "INSERT INTO plasmids(title, date, body, userid) VALUES(:title, :date, :body, :userid)";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'title' => $data['title'],
        'date' => kdate(),
        'body' => $data['body'],
        'userid' => $_SESSION['userid']));
    // END SQL main
}

// Get what is the experiment id we just created
$sql = "SELECT id FROM ".$type." WHERE userid = :userid ORDER BY id DESC LIMIT 0,1";
$req = $bdd->prepare($sql);
$req->bindParam(':userid', $_SESSION['userid']);
$req->execute();
$data = $req->fetch();
$newid = $data['id'];


if ($type === 'experiments') {
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
    // LINKS
    $linksql = "SELECT link_id FROM experiments_links WHERE item_id = ".$id;
    $linkreq = $bdd->prepare($linksql);
    $linkreq->execute();
    while($links = $linkreq->fetch()) {
        $sql = "INSERT INTO experiments_links (link_id, item_id) VALUES(:link_id, :item_id)";
        $req = $bdd->prepare($sql);
        $result = $req->execute(array(
            'link_id' => $links['link_id'],
            'item_id' => $newid
        ));
    }
}

// Check if insertion is successful and redirect to the newly created experiment in edit mode
if($result) {
// info box
$msg_arr = array();
if ($type === 'experiments') {
    $msg_arr[] = 'Experiment successfully duplicated';
}
if ($type === 'plasmids') {
    $msg_arr[] = 'Plasmid successfully duplicated';
}
$_SESSION['infos'] = $msg_arr;
    header('location: '.$type.'.php?mode=edit&id='.$newid.'');
} else {
    echo "Something went wrong in the database query. Check the flux capacitor.";
}

?>
