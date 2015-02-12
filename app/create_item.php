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
require_once ELAB_ROOT.'inc/locale.php';
$msg_arr = array();

// What do we create ?
if (isset($_GET['type']) && !empty($_GET['type']) && is_pos_int($_GET['type'])) {
    // $type is int for DB items
    $type = $_GET['type'];
} elseif (isset($_GET['type']) && !empty($_GET['type']) && ($_GET['type'] === 'exp')) {
    $type = 'experiments';
} else {
    $msg_arr[] = _('Wrong item type!');
    $_SESSION['infos'] = $msg_arr;
    header('location: ../index.php');
    exit;
}


if ($type === 'experiments') {
    $elabid = generate_elabid();
    // do we want template ?
    if (isset($_GET['tpl']) && is_pos_int($_GET['tpl'])) {
        // SQL to get template
        $sql = "SELECT name, body FROM experiments_templates WHERE id = :id";
        $get_tpl = $pdo->prepare($sql);
        $get_tpl->execute(array(
            'id' => $_GET['tpl']
        ));
        $get_tpl_info = $get_tpl->fetch();
        // the title is the name of the template
        $title = $get_tpl_info['name'];
        $body = $get_tpl_info['body'];
    } else {
        // if there is no template, title is 'Untitled' and the body is the default exp_tpl
        $title = _('Untitled');
        // SQL to get body
        $sql = "SELECT body FROM experiments_templates WHERE userid = 0 AND team = :team";
        $get_body = $pdo->prepare($sql);
        $get_body->execute(array(
            'team' => $_SESSION['team_id']
        ));
        $experiments_templates = $get_body->fetch();
        $body = $experiments_templates['body'];
    }

    // what will be the status ?
    // go pick what is the default status for the team
    // there should be only one because upon making a status default,
    // all the others are made not default
    $sql = "SELECT id FROM status WHERE is_default = true AND team = :team LIMIT 1";
    $req = $pdo->prepare($sql);
    $req->bindParam(':team', $_SESSION['team_id']);
    $req->execute();
    $status = $req->fetchColumn();

    // if there is no is_default status
    // we take the first status that come
    if (!$status) {
        $sql = 'SELECT id FROM status WHERE team = :team LIMIT 1';
        $req = $pdo->prepare($sql);
        $req->bindParam(':team', $_SESSION['team_id']);
        $req->execute();
        $status = $req->fetchColumn();
    }

    // SQL for create experiments
    $sql = "INSERT INTO experiments(team, title, date, body, status, elabid, visibility, userid) VALUES(:team, :title, :date, :body, :status, :elabid, :visibility, :userid)";
    $req = $pdo->prepare($sql);
    $result = $req->execute(array(
        'team' => $_SESSION['team_id'],
        'title' => $title,
        'date' => kdate(),
        'body' => $body,
        'status' => $status,
        'elabid' => $elabid,
        'visibility' => 'team',
        'userid' => $_SESSION['userid']
    ));
} else { // create item for DB
    // SQL to get template
    $sql = "SELECT template FROM items_types WHERE id = :id";
    $get_tpl = $pdo->prepare($sql);
    $get_tpl->execute(array(
        'id' => $type
    ));
    $get_tpl_body = $get_tpl->fetch();

    // SQL for create DB item
    $sql = "INSERT INTO items(team, title, date, body, userid, type) VALUES(:team, :title, :date, :body, :userid, :type)";
    $req = $pdo->prepare($sql);
    $result = $req->execute(array(
        'team' => $_SESSION['team_id'],
        'title' => 'Untitled',
        'date' => kdate(),
        'body' => $get_tpl_body['template'],
        'userid' => $_SESSION['userid'],
        'type' => $type
    ));
}

// Check if insertion is successful and redirect to the newly created experiment in edit mode
if ($result) {
    // info box
    $msg_arr[] = _('New item created successfully.');
    $_SESSION['infos'] = $msg_arr;
    if ($type === 'experiments') {
        header('location: ../experiments.php?mode=edit&id='.$pdo->lastInsertId().'');
        exit;
    } else {
        header('location: ../database.php?mode=edit&id='.$pdo->lastInsertId().'');
        exit;
    }
} else {
    die();
}
