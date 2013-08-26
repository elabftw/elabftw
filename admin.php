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
<script src="js/tinymce/tinymce.min.js"></script>
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

<section class='item'>
<h3>TEAM MEMBERS</h3>
<?php
// SQL to get all users
$sql = "SELECT * FROM users";
$req = $bdd->prepare($sql);
$req->execute();
while ($users = $req->fetch()) {
    ?>
    <div class='simple_border'>
    <a class='trigger_users_<?php echo $users['userid'];?>'><img src='img/profile.png' alt='' /> <?php echo "Edit ".$users['firstname'];?></a>
    <div class='toggle_users_<?php echo $users['userid'];?>'>
        <a class='align_right' href='' onClick="confirm_delete('<?php echo $users['userid']."', '".$users['lastname'];?>')"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
<br />
        <form method='post' action='admin-exec.php'>
            <input type='hidden' value='<?php echo $users['userid'];?>' name='userid' />
            <input type='text' value='<?php echo $users['firstname'];?>' name='firstname' />
            <input type='text' value='<?php echo $users['lastname'];?>' name='lastname' />
            <input type='email' value='<?php echo $users['email'];?>' name='email' /><br />
            Has admin rights ?<select name='is_admin'>
            <option value='1'<?php
                    if($users['is_admin'] == 1) {
                        echo " selected='selected'";
                    }
?>
    >yes</option>
    <option value='0'<?php
                    if($users['is_admin'] == 0) {
                        echo " selected='selected'";
                    }
?>
                    >no</option>
            </select>
<br />
            Has an active account ?<select name='validated'>
            <option value='1'<?php
                    if($users['validated'] == 1) {
                        echo " selected='selected'";
                    }
?>
    >yes</option>
    <option value='0'<?php
                    if($users['validated'] == 0) {
                        echo " selected='selected'";
                    }
?>
                    >no</option>
            </select>
<br />
Reset user password : <input type='password' value='' name='new_password' />
<br />
Repeat new password : <input type='password' value='' name='confirm_new_password' />
<br />
<br />
    <input type='submit' class='button' value='Edit this user' /><br />
        </form>
    </div>
    <script>
            $(".toggle_users_<?php echo $users['userid'];?>").hide();
            $("a.trigger_users_<?php echo $users['userid'];?>").click(function(){
                $('div.toggle_users_<?php echo $users['userid'];?>').slideToggle(1);
            });
    </script>
    </div>
    <?php
}
echo "</div>";
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
    ?>
    <div class='simple_border'>
    <a class='trigger_<?php echo $items_types['id'];?>'>Edit <?php echo $items_types['name'];?></a>
    <div class='toggle_container_<?php echo $items_types['id'];?>'>
    <a class='align_right' href='delete_item.php?id=<?php echo $items_types['id'];?>&type=item_type' onClick="return confirm('Delete this item type ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>

    <form action='admin-exec.php' method='post'>
    <input type='text' class='biginput' name='item_type_name' value='<?php echo stripslashes($items_types['name']);?>' />
    <input type='hidden' name='item_type_id' value='<?php echo $items_types['id'];?>' />

    <div id='colorwheel_div_<?php echo $items_types['id'];?>'>
    <div class='colorwheel inline'></div>

    <input type='color' name='item_type_bgcolor' value='#<?php echo $items_types['bgcolor'];?>'/></div><br /><br />
     
    <textarea class='mceditable' name='item_type_template' /><?php echo stripslashes($items_types['template']);?></textarea><br />

    <input type='submit' class='button' value='Edit <?php echo stripslashes($items_types['name']);?>' /><br />
    </form></div>
    <script>$(document).ready(function() {
        $(".toggle_container_<?php echo $items_types['id'];?>").hide();
        $("a.trigger_<?php echo $items_types['id'];?>").click(function(){
            $('div.toggle_container_<?php echo $items_types['id'];?>').slideToggle(1);
        });
        color_wheel('#colorwheel_div_<?php echo $items_types['id'];?>')
    });</script></div>
    <?php
}
?>

</section>

<section class='item'>
<a class='trigger_add_new_item'><h3>ADD NEW KIND OF DATABASE ITEM</h3></a>
<div class='simple_border toggle_add_new_item'><form action='admin-exec.php' method='post'>
<input type='text' class='biginput' name='new_item_type_name' />
<input type='hidden' name='new_item_type' value='1' />
<div id='colorwheel_div_new'>
<div class='colorwheel inline'></div>
<input type='text' name='new_item_type_bgcolor' value='#000000' /></div><br /><br />
<textarea class='mceditable' name='new_item_type_template' /></textarea><br />
<input type='submit' class='button' value='Add new item type' /></form></div>
</section>

<script>
// confirm delete by writing full name
function confirm_delete(id, lastname) {
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
// color wheel
function color_wheel(div_name) {
        var cw = Raphael.colorwheel($(div_name)[0], 80);
            cw.input($(div_name+" input" )[0]);
}
$(document).ready(function() {
    $(".toggle_users_<?php echo $users['userid'];?>").hide();
    $("a.trigger_users_<?php echo $users['userid'];?>").click(function(){
        $('div.toggle_users_<?php echo $users['userid'];?>').slideToggle(1);
    });
    $(".toggle_add_new_item").hide();
	$("a.trigger_add_new_item").click(function(){
        $('div.toggle_add_new_item').slideToggle(1);
	});
    color_wheel('#colorwheel_div_new')
    // EDITOR
    tinymce.init({
        mode : "specific_textareas",
        editor_selector : "mceditable",
        content_css : "css/tinymce.css",
        plugins : "table textcolor searchreplace code fullscreen insertdatetime paste charmap save",
        toolbar1: "undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap",
        removed_menuitems : "newdocument",
    });
});
</script>
<?php
require_once('inc/footer.php');
?>

