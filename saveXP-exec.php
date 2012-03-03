<?php
session_start();
require_once('inc/connect.php');
        header("location: experiments.php?mode=show");
/// saving 
    //$expid = $_GET['id'];
$expid='26';
$new_title = $_POST['savetitle'];
$new_date = $_POST['saveexperiment'];
$new_experiment = $_POST['saveexperiment'];
    $sql = "UPDATE experiments SET title = :title, date = :date, experiment = :experiment WHERE expid = :expid";
    $req = $bdd->prepare($sql);
    $req->execute(array(
        'title' => $new_title,
        'date' => $new_date,
        'experiment' => $new_experiment,
        'expid' => $expid));
    exit;

?>
