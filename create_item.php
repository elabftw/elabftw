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
$msg_arr = array();

// What do we create ?
if (isset($_GET['type']) && !empty($_GET['type']) && ($_GET['type'] === 'exp')){
    $type = 'experiments';
    // Is there a template ?
    if (isset($_GET['tpl']) && !empty($_GET['tpl']) && is_pos_int($_GET['tpl'])) {
        $tpl_id = $_GET['tpl'];
        $sql = "SELECT body FROM experiments_templates WHERE id = ".$tpl_id;
        $tplreq = $bdd->prepare($sql);
        $tplreq->execute();
        $data = $tplreq->fetch();
        $body = $data['body'];
    } else { // no template
        $body = '';
    }
} elseif (isset($_GET['type']) && !empty($_GET['type']) && ($_GET['type'] === 'pro')){
    $type = 'protocols';
} elseif (isset($_GET['type']) && !empty($_GET['type']) && ($_GET['type'] === 'pla')){
    $type = 'plasmids';
} elseif (isset($_GET['type']) && !empty($_GET['type']) && ($_GET['type'] === 'ant')){
    $type = 'antibodies';
} elseif (isset($_GET['type']) && !empty($_GET['type']) && ($_GET['type'] === 'sir')){
    $type = 'sirna';
} elseif (isset($_GET['type']) && !empty($_GET['type']) && ($_GET['type'] === 'pap')){
    $type = 'papers';
} elseif (isset($_GET['type']) && !empty($_GET['type']) && ($_GET['type'] === 'lab')){
    $type = 'labmeetings';
} else {
    $msg_arr[] = 'Wrong item type !';
    $_SESSION['infos'] = $msg_arr;
    header('location: index.php');
    exit();
}

if ($type == 'experiments'){
// Generate unique elabID
$date = kdate();
$elabid = $date."-".sha1(uniqid($date, TRUE));

// SQL for create experiments
$sql = "INSERT INTO experiments(title, date, body, outcome, elabid, userid) VALUES(:title, :date, :body, :outcome, :elabid, :userid)";
$req = $bdd->prepare($sql);
$result = $req->execute(array(
    'title' => 'Untitled',
    'date' => kdate(),
    'body' => $body,
    'outcome' => 'running',
    'elabid' => $elabid,
    'userid' => $_SESSION['userid']));
}

if ($type == 'protocols') {
// SQL for create protocols
$sql = "INSERT INTO items(title, date, body, userid, type) VALUES(:title, :date, :body, :userid, :type)";
$req = $bdd->prepare($sql);
$result = $req->execute(array(
    'title' => 'Untitled',
    'date' => kdate(),
    'body' => '',
    'userid' => $_SESSION['userid'],
    'type' => substr($type, 0, 3)
    ));
}

if ($type == 'plasmids'){
    // SQL to get plasmid template
    $sql = "SELECT body FROM items_templates WHERE type = 'pla'";
    $pla_tpl = $bdd->prepare($sql);
    $pla_tpl->execute();
    $pla_tpl_body = $pla_tpl->fetch();

    // SQL for create plasmids
    $sql = "INSERT INTO items(title, date, body, userid, type) 
        VALUES(:title, :date, :body, :userid, :type)";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'title' => 'Untitled',
        'date' => kdate(),
        'body' => $pla_tpl_body['body'],
        'userid' => $_SESSION['userid'],
        'type' => 'pla'
        ));
}
if ($type == 'antibodies'){
    // SQL to get plasmid template
    $sql = "SELECT body FROM items_templates WHERE type = 'ant'";
    $ant_tpl = $bdd->prepare($sql);
    $ant_tpl->execute();
    $ant_tpl_body = $ant_tpl->fetch();

    // SQL for create antsmids
    $sql = "INSERT INTO items(title, date, body, userid, type) 
        VALUES(:title, :date, :body, :userid, :type)";
    $req = $bdd->prepare($sql);
    $result = $req->execute(array(
        'title' => 'Untitled',
        'date' => kdate(),
        'body' => $ant_tpl_body['body'],
        'userid' => $_SESSION['userid'],
        'type' => 'ant'
        ));
}
// Get what is the item id we just created
if ($type === 'experiments') {
    $sql = "SELECT id FROM experiments WHERE userid = :userid ORDER BY id DESC LIMIT 0,1";
} else {
    $sql = "SELECT id FROM items WHERE userid = :userid ORDER BY id DESC LIMIT 0,1";
}
$req = $bdd->prepare($sql);
$req->bindParam(':userid', $_SESSION['userid']);
$req->execute();
$data = $req->fetch();
$newid = $data['id'];

// Check if insertion is successful and redirect to the newly created experiment in edit mode
if($result) {
    // info box
    $msg_arr[] = 'New item successfully created.';
    $_SESSION['infos'] = $msg_arr;
    if ($type === 'experiments') {
        header('location: experiments.php?mode=edit&id='.$newid.'');
    } else {
        header('location: database.php?mode=edit&id='.$newid.'');
    }
} else {
    die("Something went wrong in the database query. Check the flux capacitor.");
}
?>
