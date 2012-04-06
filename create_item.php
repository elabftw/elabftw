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
    if (isset($_GET['tpl']) && !empty($_GET['tpl']) && filter_var($_GET['tpl'], FILTER_VALIDATE_INT)) {
        $tpl_id = $_GET['tpl'];
        $sql = "SELECT body FROM experiments_templates WHERE id = ".$tpl_id;
        $tplreq = $bdd->prepare($sql);
        $tplreq->execute();
        $data = $tplreq->fetch();
        $body = $data['body'];
    } else { // no template
        $body = '';
    }
} elseif (isset($_GET['type']) && !empty($_GET['type']) && ($_GET['type'] === 'prot')){
    $type = 'protocols';
} else {
    $msg_arr[] = 'Wrong item type !';
    $_SESSION['infos'] = $msg_arr;
    header('location: experiments.php');
    exit();
}

if (isset($_GET['type']) && !empty($_GET['type']) && ($_GET['type'] === 'exp')){
    $type = 'experiments';
} elseif (isset($_GET['type']) && !empty($_GET['type']) && ($_GET['type'] === 'prot')){
    $type = 'protocols';
} else {
    $msg_arr[] = 'Wrong item type !';
    $_SESSION['infos'] = $msg_arr;
    header('location: experiments.php');
    exit();
}
if ($type == 'experiments'){
// SQL for create experiments
$sql = "INSERT INTO ".$type."(title, date, body, outcome, userid) VALUES(:title, :date, :body, :outcome, :userid)";
$req = $bdd->prepare($sql);
$result = $req->execute(array(
    'title' => 'Untitled',
    'date' => kdate(),
    'body' => $body,
    'outcome' => 'running',
    'userid' => $_SESSION['userid']));
}

if ($type == 'protocols'){
// SQL for create protocols
$sql = "INSERT INTO ".$type."(title, date, body, userid) VALUES(:title, :date, :body, :userid)";
$req = $bdd->prepare($sql);
$result = $req->execute(array(
    'title' => 'Untitled',
    'date' => kdate(),
    'body' => '',
    'userid' => $_SESSION['userid']));
}
// Get what is the item id we just created
$sql = "SELECT id FROM ".$type." WHERE userid = :userid ORDER BY id DESC LIMIT 0,1";
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
    header('location: '.$type.'.php?mode=edit&id='.$newid.'');
} else {
    die("Something went wrong in the database query. Check the flux capacitor.");
}
?>
