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
require_once 'inc/common.php';
if ($_SESSION['is_admin'] != 1) {
    die('You are not admin !');
}
$page_title = 'Admin Panel';
require_once 'inc/head.php';
require_once 'inc/menu.php';
require_once 'inc/info_box.php';
// formkey stuff
require_once 'lib/classes/formkey.class.php';
$formKey = new formKey();
?>
<script src="js/tinymce/tinymce.min.js"></script>
<script src="js/raphael-2.1.0.min.js"></script>
<script src="js/colorwheel.min.js"></script>
<?php
// MAIN SQL FOR USERS
$sql = "SELECT * FROM users WHERE validated = :validated";
$user_req = $pdo->prepare($sql);
$user_req->bindValue(':validated', 0);
$user_req->execute();
$count = $user_req->rowCount();
// only show the frame if there is some users to validate
if ($count > 0) {
    $message = "There are users waiting for validation of their account :";
    $message .= "<form method='post' action='admin-exec.php'><ul>";
    while ($data = $user_req->fetch()) {
        $message .= "<li><label>
            <input type='checkbox' name='validate[]' 
            value='".$data['userid']."'> ".$data['firstname']." ".$data['lastname']." (".$data['email'].")
            </label></li>";
    }
    $message .= "</ul><div class='center'>
    <input class='button' type='submit' value='Validate users' /></div></form>";
    display_message('error', $message);
}
?>

<div id='tabs'>
<ul>
<li><a href='#tabs-1'>Main configuration</a></li>
<li><a href='#tabs-2'>Users</a></li>
<li><a href='#tabs-3'>Status</a></li>
<li><a href='#tabs-4'>Types of items</a></li>
</ul>

<!-- TABS 1 -->
<div id='tabs-1'>

    <h3>CONFIGURATION</h3>
    <form method='post' action='admin-exec.php'>
    <div id='config_form'>
        <label for='lab_name'>Name of the lab :</label>
        <input type='text' value='<?php echo get_config('lab_name');?>' name='lab_name' id='lab_name' />
    <br />
    <br />
        <label for='admin_validate'>Users need validation by admin after registration :</label>
        <select name='admin_validate' id='admin_validate'>
            <option value='1'<?php
                if (get_config('admin_validate') == 1) { echo " selected='selected'"; } ?>
            >yes</option>
            <option value='0'<?php
                    if (get_config('admin_validate') == 0) { echo " selected='selected'"; } ?>
            >no</option>
        </select>
    <br />
    <br />
        <label for='deletable_xp'>Users can delete experiments :</label>
        <select name='deletable_xp' id='deletable_xp'>
            <option value='1'<?php
                if (get_config('deletable_xp') == 1) { echo " selected='selected'"; } ?>
            >yes</option>
            <option value='0'<?php
                    if (get_config('deletable_xp') == 0) { echo " selected='selected'"; } ?>
            >no, only the admin can</option>
        </select>
    <br />
    <br />
        <label for='debug'>Activate debug mode :</label>
        <select name='debug' id='debug'>
            <option value='1'<?php
                if (get_config('debug') == 1) { echo " selected='selected'"; } ?>
            >yes</option>
            <option value='0'<?php
                    if (get_config('debug') == 0) { echo " selected='selected'"; } ?>
            >no</option>
        </select>
    <br />
    <br />
        <label for='link_name'>Name of the link in the main menu :</label>
        <input type='text' value='<?php echo get_config('link_name');?>' name='link_name' id='link_name' />
    <br />
    <br />
        <label for='link_href'>Address where this link should point :</label>
        <input type='url' value='<?php echo get_config('link_href');?>' name='link_href' id='link_href' />
    <br />
    <br />
        <label for='path'>Full path to the install folder :</label>
        <input type='text' value='<?php echo get_config('path');?>' name='path' id='path' />
    <br />
    <br />
        <label for='proxy'>Address of the proxy :</label>
        <input type='text' value='<?php echo get_config('proxy');?>' name='proxy' id='proxy' />
    <br />
    <br />
        <label for='smtp_address'>Address of the SMTP server :</label>
        <input type='text' value='<?php echo get_config('smtp_address');?>' name='smtp_address' id='smtp_address' />
    <br />
    <br />
        <label for='smtp_encryption'>SMTP encryption (can be TLS or STARTSSL):</label>
        <input type='text' value='<?php echo get_config('smtp_encryption');?>' name='smtp_encryption' id='smtp_encryption' />
    <br />
    <br />
        <label for='smtp_port'>SMTP port :</label>
        <input type='text' value='<?php echo get_config('smtp_port');?>' name='smtp_port' id='smtp_port' />
    <br />
    <br />
        <label for='smtp_username'>SMTP username :</label>
        <input type='text' value='<?php echo get_config('smtp_username');?>' name='smtp_username' id='smtp_username' />
    <br />
    <br />
        <label for='smtp_password'>SMTP password :</label>
        <input type='password' value='<?php echo get_config('smtp_password');?>' name='smtp_password' id='smtp_password' />
    <br />
    <br />
        <label for='login_tries'>Number of allowed login attempts :</label>
        <input type='text' value='<?php echo get_config('login_tries');?>' name='login_tries' id='login_tries' />
    <br />
    <br />
        <label for='ban_time'>Time of the ban after failed login attempts (in minutes) :</label>
        <input type='text' value='<?php echo get_config('ban_time');?>' name='ban_time' id='ban_time' />
    <br />
    <br />
    </div>
    <div class='center'>
        <input type='submit' name='submit_config' class='submit button' value='Save' />
    </div>
    </form>

</div>

<!-- TABS 2 -->
<div id='tabs-2'>

    <h3>TEAM MEMBERS</h3>
    <?php
    // we show only the validated users here
    $user_req->bindValue(':validated', 1);
    $user_req->execute();
    while ($users = $user_req->fetch()) {
        ?>
        <div class='simple_border'>
            <a class='trigger_users_<?php echo $users['userid'];?>'><img src='img/profile.png' alt='profile' /> <?php echo "Edit ".$users['firstname'];?></a>
            <div class='toggle_users_<?php echo $users['userid'];?>'>
        <br />
                <form method='post' action='admin-exec.php' id='admin_user_form'>
                    <input type='hidden' value='<?php echo $users['userid'];?>' name='userid' />
                    <label for='edituser_firstname'>Firstname</label>
                    <input  id='edituser_firstname' type='text' value='<?php echo $users['firstname'];?>' name='firstname' />
                    <label for='edituser_lastname'>Lastname</label>
                    <input  id='edituser_lastname' type='text' value='<?php echo $users['lastname'];?>' name='lastname' />
                    <label for='edituser_username'>Username</label>
                    <input  id='edituser_username' type='text' value='<?php echo $users['username'];?>' name='username' />
                    <label for='edituser_email'>Email</label>
                    <input id='edituser_email' type='email' value='<?php echo $users['email'];?>' name='email' /><br />
                    Has admin rights ?<select name='is_admin'>
                    <option value='1'<?php
                            if($users['is_admin'] == 1) {
                                echo " selected='selected'";
                            }
        ?>
            >yes</option>
            <option value='0'<?php
                            if ($users['is_admin'] == 0) {
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
                    if ($users['validated'] == 1) { echo " selected='selected'"; } ?>
            >yes</option>
            <option value='0'<?php
                if ($users['validated'] == 0) { echo " selected='selected'"; } ?>
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
<section class='simple_border' style='background-color:#FF8080;'>

    <h3>DANGER ZONE</h3>
    <h4>Delete a user</h4>
    <form action='admin-exec.php' method='post'>
        <!-- form key -->
        <?php $formKey->output_formkey(); ?>
        <label for'delete_user'>Type EMAIL ADDRESS of a member to delete this user and all his experiments/files forever.</label>
        <input type='email' name='delete_user' id='delete_user' />
    <div class='center'>
        <input type='submit' class='button submit' value='Delete this user !' />
    </div>
    </form>
</section>

</div>

<!-- TABS-3 -->
<div id='tabs-3'>

    <h3>STATUS</h3>
    <?php
    // SQL to get all status
    $sql = "SELECT * from status";
    $req = $pdo->prepare($sql);
    $req->execute();
    while ($status = $req->fetch()) {
        // count the experiments with this status
        // don't allow deletion if experiments with this status exist
        // but instead display a message to explain
        $count_exp_sql = "SELECT COUNT(id) FROM experiments WHERE status = :status";
        $count_exp_req = $pdo->prepare($count_exp_sql);
        $count_exp_req->bindParam(':status', $status['id'], PDO::PARAM_INT);
        $count_exp_req->execute();
        $count = $count_exp_req->fetchColumn();
        ?>
        <div class='simple_border'>
        <a class='trigger_status_<?php echo $status['id'];?>'>Edit <?php echo $status['name'];?></a>
        <div class='toggle_container_status_<?php echo $status['id'];?>'>
        <?php
        if ($count == 0) {
            ?>
            <img class='align_right' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' onClick="deleteThis('<?php echo $status['id'];?>','status', 'admin.php')" />
        <?php
        } else {
            ?>
            <img class='align_right' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' onClick="alert('Remove all experiments with this status before deleting this status.')" />
        <?php
        }
        ?>

        <form action='admin-exec.php' method='post'>
            <input type='text' name='status_name' value='<?php echo stripslashes($status['name']);?>' />
            <label for='default_checkbox'>Make default</label>
            <input type='checkbox' name='status_is_default' id='default_checkbox'
            <?php
            // check the box if the status is already default
            if ($status['is_default'] == 1) {
                echo " checked";
            }
            ?>>
            <div id='colorwheel_div_edit_status_<?php echo $status['id'];?>'>
            <div class='colorwheel inline'></div>
            <input type='text' name='status_color' value='#<?php echo $status['color'];?>' />
            </div>
            <input type='hidden' name='status_id' value='<?php echo $status['id'];?>' />
            <br />

            <div class='center'>
            <input type='submit' class='button' value='Edit <?php echo stripslashes($status['name']);?>' /><br />
            </div>
        </form></div>
        <script>$(document).ready(function() {
            $(".toggle_container_status_<?php echo $status['id'];?>").hide();
            $("a.trigger_status_<?php echo $status['id'];?>").click(function(){
                $('div.toggle_container_status_<?php echo $status['id'];?>').slideToggle(1);
            });
            color_wheel('#colorwheel_div_edit_status_<?php echo $status['id'];?>')
        });</script></div>
        <?php
    }
    ?>

    <section class='simple_border'>
        <h3>ADD NEW STATUS</h3>
        <form action='admin-exec.php' method='post'>
            <input type='text' class='biginput' name='new_status_name' />
            <div id='colorwheel_div_new_status'>
                <div class='colorwheel inline'></div>
                <input type='text' name='new_status_color' value='#000000' />
            </div>
            <br />
            <div class='center'>
                <input type='submit' class='submit button' value='Add new status' />
            </div>
        </form>
    </section>

</div>

<!-- TABS 4 ITEMS TYPES-->
<div id='tabs-4'>
<a id='items_types'></a>
<h3>EXISTING TYPES</h3>
<?php
// SQL to get all items type
$sql = "SELECT * from items_types";
$req = $pdo->prepare($sql);
$req->execute();
while ($items_types = $req->fetch()) {
    ?>
    <div class='simple_border'>
    <a class='trigger_<?php echo $items_types['id'];?>'>Edit <?php echo $items_types['name'];?></a>
    <div class='toggle_container_<?php echo $items_types['id'];?>'>
    <?php
    // count the items with this type
    // don't allow deletion if items with this type exist
    // but instead display a message to explain
    $count_db_sql = "SELECT COUNT(id) FROM items WHERE type = :type";
    $count_db_req = $pdo->prepare($count_db_sql);
    $count_db_req->bindParam(':type', $items_types['id'], PDO::PARAM_INT);
    $count_db_req->execute();
    $count = $count_db_req->fetchColumn();
    if ($count == 0) {
        ?>
        <img class='align_right' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' onClick="deleteThis('<?php echo $items_types['id'];?>','item_type', 'admin.php')" />
    <?php
    } else {
        ?>
        <img class='align_right' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' onClick="alert('Remove all database items with this type before deleting this type.')" />
    <?php
    }
    ?>
    <form action='admin-exec.php' method='post'>
    <input type='text' class='biginput' name='item_type_name' value='<?php echo stripslashes($items_types['name']);?>' />
    <input type='hidden' name='item_type_id' value='<?php echo $items_types['id'];?>' />

    <div id='colorwheel_div_<?php echo $items_types['id'];?>'>
    <div class='colorwheel inline'></div>

    <input type='color' name='item_type_bgcolor' value='#<?php echo $items_types['bgcolor'];?>'/></div><br /><br />
     
    <textarea class='mceditable' name='item_type_template' /><?php echo stripslashes($items_types['template']);?></textarea><br />
    <div class='center'>
    <input type='submit' class='button' value='Edit <?php echo stripslashes($items_types['name']);?>' /><br />
    </div>
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


<section class='simple_border'>
    <h3>ADD NEW TYPE OF DATABASE ITEM</h3>
    <form action='admin-exec.php' method='post'>
        <input type='text' class='biginput' name='new_item_type_name' />
        <input type='hidden' name='new_item_type' value='1' />
        <div id='colorwheel_div_new'>
        <div class='colorwheel inline'></div>
        <input type='text' name='new_item_type_bgcolor' value='#000000' /></div><br /><br />
        <textarea class='mceditable' name='new_item_type_template' /></textarea><br />
        <div class='center'>
        <input type='submit' class='button' value='Add new item type' />
        </div>
    </form>
</section>
</div>

</div>

<script>
// color wheel
function color_wheel(div_name) {
        var cw = Raphael.colorwheel($(div_name)[0], 80);
            cw.input($(div_name+" input" )[0]);
}
$(document).ready(function() {
    // TABS
    $('#tabs').tabs();
    $('#tabs li');
    // TOGGLE
    $(".toggle_users_<?php echo $users['userid'];?>").hide();
    $("a.trigger_users_<?php echo $users['userid'];?>").click(function(){
        $('div.toggle_users_<?php echo $users['userid'];?>').slideToggle(1);
    });
    color_wheel('#colorwheel_div_new')
    color_wheel('#colorwheel_div_new_status')
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
<?php require_once 'inc/footer.php';
