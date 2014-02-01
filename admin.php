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
// formkey stuff
require_once('lib/classes/formkey.class.php');
$formKey = new formKey();
?>
<script src="js/tinymce/tinymce.min.js"></script>
<script src="js/raphael-2.1.0.min.js"></script>
<script src="js/colorwheel.min.js"></script>
<?php

// SQL to get all unvalidated users
$sql = "SELECT userid, lastname, firstname, email FROM users WHERE validated = 0"; $req = $bdd->prepare($sql);
$req->execute();
$count = $req->rowCount();
// only show the frame if there is some users to validate
if ($count > 0) {
    $message = "There are users waiting for validation of their account :";
    $message .= "<form method='post' action='admin-exec.php'><ul>";
while ($data = $req->fetch()) {
    $message .= "<li><label><input type='checkbox' name='validate[]' value='".$data['userid']."'> ".$data['firstname']." ".$data['lastname']." (".$data['email'].")</label></li>";
}
    $message .= "</ul><div class='center'>
    <input class='button' type='submit' value='Validate users' /></div></form>";
    display_message('error', $message);
}
?>

<!-- CONFIGURATION FORM -->
<section class='item'>
<h3>CONFIGURATION</h3>
<?php
// SQL to get all config
$sql = "SELECT * FROM config";
$req = $bdd->prepare($sql);
$req->execute();
$config = $req->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
?>
<form method='post' action='admin-exec.php'>
<div id='config_form'>
    <label for='lab_name'>Name of the lab :</label>
    <input type='text' value='<?php echo $config['lab_name'][0];?>' name='lab_name' id='lab_name' />
<br />
<br />
    <label for='admin_validate'>Users need validation by admin after registration :</label>
    <select name='admin_validate' id='admin_validate'>
        <option value='1'<?php
            if ($config['admin_validate'][0] == 1) { echo " selected='selected'"; } ?>
        >yes</option>
        <option value='0'<?php
                if ($config['admin_validate'][0] == 0) { echo " selected='selected'"; } ?>
        >no</option>
    </select>
<br />
<br />
    <label for='deletable_xp'>Users can delete experiments :</label>
    <select name='deletable_xp' id='deletable_xp'>
        <option value='1'<?php
            if ($config['deletable_xp'][0] == 1) { echo " selected='selected'"; } ?>
        >yes</option>
        <option value='0'<?php
                if ($config['deletable_xp'][0] == 0) { echo " selected='selected'"; } ?>
        >no, only the admin can</option>
    </select>
<br />
<br />
    <label for='debug'>Activate debug mode :</label>
    <select name='debug' id='debug'>
        <option value='1'<?php
            if ($config['debug'][0] == 1) { echo " selected='selected'"; } ?>
        >yes</option>
        <option value='0'<?php
                if ($config['debug'][0] == 0) { echo " selected='selected'"; } ?>
        >no</option>
    </select>
<br />
<br />
    <label for='link_name'>Name of the link in the main menu :</label>
    <input type='text' value='<?php echo $config['link_name'][0];?>' name='link_name' id='link_name' />
<br />
<br />
    <label for='link_href'>Address where this link should point :</label>
    <input type='url' value='<?php echo $config['link_href'][0];?>' name='link_href' id='link_href' />
<br />
<br />
    <label for='path'>Full path to the install folder :</label>
    <input type='text' value='<?php echo $config['path'][0];?>' name='path' id='path' />
<br />
<br />
    <label for='proxy'>Address of the proxy :</label>
    <input type='text' value='<?php echo $config['proxy'][0];?>' name='proxy' id='proxy' />
<br />
<br />
    <label for='smtp_address'>Address of the SMTP server :</label>
    <input type='text' value='<?php echo $config['smtp_address'][0];?>' name='smtp_address' id='smtp_address' />
<br />
<br />
    <label for='smtp_encryption'>SMTP encryption (can be TLS or STARTSSL):</label>
    <input type='text' value='<?php echo $config['smtp_encryption'][0];?>' name='smtp_encryption' id='smtp_encryption' />
<br />
<br />
    <label for='smtp_port'>SMTP port :</label>
    <input type='text' value='<?php echo $config['smtp_port'][0];?>' name='smtp_port' id='smtp_port' />
<br />
<br />
    <label for='smtp_username'>SMTP username :</label>
    <input type='text' value='<?php echo $config['smtp_username'][0];?>' name='smtp_username' id='smtp_username' />
<br />
<br />
    <label for='smtp_password'>SMTP password :</label>
    <input type='password' value='<?php echo $config['smtp_password'][0];?>' name='smtp_password' id='smtp_password' />
<br />
<br />
</div>
<div class='center'>
    <input type='submit' name='submit_config' class='button' value='Edit config' /><br />
</div>
</form>

</section>

<!-- TEAM MEMBERS -->
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
    <a class='trigger_users_<?php echo $users['userid'];?>'><img src='img/profile.png' alt='profile' /> <?php echo "Edit ".$users['firstname'];?></a>
    <div class='toggle_users_<?php echo $users['userid'];?>'>
<br />
        <form method='post' action='admin-exec.php' id='admin_user_form'>
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
            Can lock experiments of others ?<select name='can_lock'>
            <option value='1'<?php
                    if ($users['can_lock'] == 1) { echo " selected='selected'"; } ?>
    >yes</option>
    <option value='0'<?php
                    if ($users['can_lock'] == 0) { echo " selected='selected'"; } ?>
                    >no</option>
            </select>
<br />
<label for'validated'>Has an active account ?</label>
<select name='validated' id='validated'>
    <option value='1'<?php
            if($users['validated'] == 1) { echo " selected='selected'"; } ?>
    >yes</option>
    <option value='0'<?php
        if($users['validated'] == 0) { echo " selected='selected'"; } ?>
    >no</option>
</select>
<br />
Reset user password : <input type='password' value='' name='new_password' />
<br />
Repeat new password : <input type='password' value='' name='confirm_new_password' />
<br />
<br />
<div class='center'>
    <input type='submit' class='button' value='Edit this user' />
</div>
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
?>
</div>
</section>

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
<img class='align_right' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' onClick="deleteThis('<?php echo $items_types['id'];?>','item_type', 'info=Item type deleted successfully', 'admin.php')" />

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

<section class='item' style='background-color:#FF8080'>
<h3>DANGER ZONE</h3>
<h4>Delete a user</h4>
<p>
<form action='admin-exec.php' method='post'>
    <!-- form key -->
    <?php $formKey->output_formkey(); ?>
    <label for'delete_user'>Type EMAIL ADDRESS of a member to delete this user and all his experiments/files forever.</label>
    <input type='email' name='delete_user' id='delete_user' /><br />
<br />
<div class='center'>
    <input type='submit' class='button' value='Delete this user !' />
</div>
</form>


</section>
<script>
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
        toolbar1: "undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap",
        removed_menuitems : "newdocument",
    });
});
</script>
<?php require_once('inc/footer.php'); ?>

