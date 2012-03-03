<?php
// INFO BOX
if( isset($_SESSION['errors']) && is_array($_SESSION['errors']) && count($_SESSION['errors']) >0 ) {
    echo '<ul class="err">';
    foreach($_SESSION['errors'] as $msg) {
        echo '<li>',$msg,'</li>'; 
    }
    echo '</ul>';
    unset($_SESSION['errors']);
}
if( isset($_SESSION['infos']) && is_array($_SESSION['infos']) && count($_SESSION['infos']) >0 ) {
    echo "<ul class='infos'>";
    foreach($_SESSION['infos'] as $msg) {
        echo "<li>".$msg."</li>";
    }
    echo "</ul>";
    unset($_SESSION['infos']);
}
?>
