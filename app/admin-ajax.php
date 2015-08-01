<?php
require_once '../inc/common.php';

// only admin can use this
if ($_SESSION['is_admin'] != 1 || $_SERVER['REQUEST_METHOD'] != 'POST') {
    die(_('This section is out of your reach.'));
}

// CREATE TEAM GROUP
if (isset($_POST['create_teamgroup']) && !empty($_POST['create_teamgroup'])) {
    $group_name = filter_var($_POST['create_teamgroup'], FILTER_SANITIZE_STRING);
    $sql = "INSERT INTO team_groups(name, team) VALUES(:name, :team)";
    $req = $pdo->prepare($sql);
    $req->bindParam(':name', $group_name);
    $req->bindParam(':team', $_SESSION['team_id']);
    if ($req->execute()) {
        echo '1';
    } else {
        echo '0';
    }
}

// EDIT TEAM GROUP NAME FROM JEDITABLE
if (isset($_POST['teamgroup']) && !empty($_POST['teamgroup'])) {
    $name = filter_var($_POST['teamgroup'], FILTER_SANITIZE_STRING);
    $id_arr = explode('_', $_POST['id']);
    if ($id_arr[0] === 'teamgroup' && is_pos_int($id_arr[1])) {
        // SQL to update single exp comment
        $sql = "UPDATE team_groups SET name = :name WHERE id = :id AND team = :team";
        $req = $pdo->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':team', $_SESSION['team_id']);
        $req->bindParam(':id', $id_arr[1], PDO::PARAM_INT);
        if ($req->execute()) {
            echo stripslashes($name);
        }
    }
}

// ADD OR REMOVE USER TO/FROM TEAM GROUP
if (isset($_POST['teamgroup_user'])) {
    if ($_POST['action'] === 'add') {
        $sql = "INSERT INTO users2team_groups(userid, groupid) VALUES(:userid, :groupid)";
    } else {
        $sql = "DELETE FROM users2team_groups WHERE userid = :userid AND groupid = :groupid";
    }
    $req = $pdo->prepare($sql);
    $req->bindParam(':userid', $_POST['teamgroup_user'], PDO::PARAM_INT);
    $req->bindParam(':groupid', $_POST['teamgroup_group'], PDO::PARAM_INT);
    if ($req->execute()) {
        echo '1';
    } else {
        echo '0';
    }
}
