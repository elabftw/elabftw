<?php
require_once('inc/connect.php');
// ADD elabid in experiments table
$sql = "SELECT * from experiments";
$req = $bdd->prepare($sql);
$req->execute();
$test = $req->fetch();
if(isset($test['elabid'])) {
    echo 'Nothing to update. Please delete this file from your server.';
} else {
    echo 'Creating field...';
    $sql = "ALTER TABLE `experiments` ADD `elabid` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
    $req = $bdd->prepare($sql);
    $result = $req->execute();
    if($result) {
        echo 'Database successfully updated, you can now delete this file.';
    } else {
        echo 'There was a problem in the database update :/';
    }
}

// ADD locked in experiments table
if(isset($test['locked'])) {
    echo 'Nothing to update. Please delete this file from your server.';
} else {
    echo 'Creating field...';
    $sql = "ALTER TABLE `experiments` ADD `locked` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0'";
    $req = $bdd->prepare($sql);
    $result = $req->execute();
    if($result) {
        echo 'Database successfully updated, you can now delete this file.';
    } else {
        echo 'There was a problem in the database update :/';
    }
}
?>
