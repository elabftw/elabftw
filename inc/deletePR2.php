<?php
// Check id is valid and assign it to $id
if(filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id = $_GET['id'];
} else {
    die("The id parameter in the URL isn't a valid experiment ID");
}
// SQL for delete PR
    require_once("inc/connect.php");
            $sql = "DELETE FROM protocols WHERE id = $id";
            $req = $bdd->prepare($sql);
            $req->execute();
            $req->closeCursor();
echo "<script>setTimeout('top.location = \'protocols.php\'', 1);</script>";
?>

