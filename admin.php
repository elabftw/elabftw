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
/* admin.php - for administration of the elab */
require_once('inc/common.php');
if ($_SESSION['is_admin'] != 1) {die('You are not admin !');}
$page_title = 'Admin Panel';
require_once('inc/head.php');
require_once('inc/menu.php');
require_once('inc/info_box.php');
?>
<script src="js/tiny_mce/tiny_mce.js"></script>
<script src="js/raphael.js"></script>
<script src="js/colorwheel.js"></script>
<?php

// SQL to get all unvalidated users
$sql = "SELECT userid, lastname, firstname, email FROM users WHERE validated = 0"; $req = $bdd->prepare($sql);
$req->execute();
$count = $req->rowCount();
// only show the frame if there is some users to validate
if ($count > 0) {
    echo "
<section class='fail'>
<h3>USERS WAITING FOR VALIDATION</h3>";
echo "<form method='post' action='admin-exec.php'><ul>";
while ($data = $req->fetch()) {
    echo "<li><input type='checkbox' name='validate[]' value='".$data['userid']."'> ".$data['firstname']." ".$data['lastname']." (".$data['email'].")</li>";
}
echo "</ul><input type='submit' name='submit' value='Validate users' /></form>";
echo "</section>";
}
?>

<section id='users' class='item'>
<h3>TEAM MEMBERS</h3>
<?php
// SQL to get all users
$sql = "SELECT userid, lastname, firstname, email FROM users WHERE validated = 1";
$req = $bdd->prepare($sql);
$req->execute();
echo "<form method='post' action='admin-exec.php'><ul>";
while ($users = $req->fetch()) {
    echo "<li>".$users['firstname']." ".$users['lastname']." (".$users['email'].") :: "; //switch to html because of JS ?>
    <a href='#' onClick="confirm_delete('<?php echo $users['userid']."', '".$users['lastname'];?>')">delete</a> 
<?php echo "<a href='admin-exec.php?edituser=".$users['userid']."'>edit</a></li>";
}
echo "</section>";
?>

<section class='item'>
<a id='items_types'></a>
<h3>DATABASE ITEMS TYPE</h3>
<?php
// SQL to get all items type
$sql = "SELECT * from items_types";
$req = $bdd->prepare($sql);
$req->execute();
while ($items_types = $req->fetch()) {
    echo "<div class='simple_border'><form action='admin-exec.php' method='post'>";
    echo "<input type='text' class='biginput' name='item_type_name' value='".$items_types['name']."' />";
    echo "<input type='hidden' name='item_type_id' value='".$items_types['id']."' />";
    echo "<div id='colorwheel_div_".$items_types['id']."'>";
    echo "<div class='colorwheel inline'></div>";
    echo "<input type='text' name='item_type_bgcolor' value='#".$items_types['bgcolor']."'/></div><br /><br />";
    echo "<textarea class='mceditable high' name='item_type_template' />".$items_types['template']."</textarea>";
    echo "<br /><input type='submit' class='submitbutton' value='Edit ".$items_types['name']."' /><br />";
    echo "</form></div>";
    echo "<script>$(document).ready(function() {
        color_wheel('#colorwheel_div_".$items_types['id']."')
});</script>";
}
?>

</section>

<script>
// color wheel
function color_wheel(div_name) {
        var cw = Raphael.colorwheel($(div_name)[0], 80);
            cw.input($(div_name+" input" )[0]);
}
$(document).ready(function() {
// EDITOR
tinyMCE.init({
    theme : "advanced",
    mode : "specific_textareas",
    editor_selector : "mceditable",
    content_css : "css/tinymce.css",
    theme_advanced_toolbar_location : "top",
    theme_advanced_font_sizes: "10px,12px,13px,14px,16px,18px,20px",
    plugins : "table",
    theme_advanced_buttons3_add : "forecolor, backcolor, tablecontrols",
    font_size_style_values : "10px,12px,13px,14px,16px,18px,20px"
});
// confirm delete by writing full name
var confirm_delete = function(id, lastname) {
    var user_input = prompt('WARNING !\nAre you absolutely sure you want to delete this user ?\nThis will delete forever ALL the user\'s data, including files and experiments !!!!\nTo confirm type the LASTNAME of the user in capital letters :');
    if(user_input != '' && user_input === lastname){
    // POST request to delete user
    var jqxhr = $.post('admin-exec.php', {
        deluser: id
    })
    // reload page
    .success(function() {location.reload()
    });
    }else{
        return false;
    }
}

});
</script>
<?php
require_once('inc/footer.php');
?>
