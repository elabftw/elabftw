<?php
session_start();
require_once("inc/connect.php");
/// saving 
    //$expid = $_GET['id'];
$expid='23';
$new_title = $_POST['edit_title'];
$new_date = 'new date';
$new_experiment = 'new exp'; 
    $sql = "UPDATE experiments SET title = :title, date = :date, experiment = :experiment WHERE expid = :expid";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'title' => $new_title,
        'date' => $new_date,
        'experiment' => $new_experiment,
        'expid' => $expid));
$req = NULL;

