<?php
// Check id is valid and assign it to $id
if(filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id = $_GET['id'];
} else {
    die("The id parameter in the URL isn't a valid experiment ID");
}
    echo "<section class='delete'><img src='img/warning.png' alt='warning' /><p>Are you sure you want to delete this document ?<br />
    It will be deleted forever (that's a very long time) !</p>
    <p id='yesno'><a href='protocols.php?mode=delete2&id=".$id."'>YES</a> ___:p___ <a href='protocols.php'>NO</a></p></section>";
?>
