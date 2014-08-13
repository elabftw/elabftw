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
<script src="bower_components/tinymce/tinymce.min.js"></script>
<script src="bower_components/raphael/raphael-min.js"></script>
<script src="bower_components/colorwheel/colorwheel.js"></script>
<?php
// MAIN SQL FOR USERS
$sql = "SELECT * FROM users WHERE validated = :validated AND team = :team";
$user_req = $pdo->prepare($sql);
$user_req->bindValue(':validated', 0);
$user_req->bindValue(':team', $_SESSION['team_id']);
$user_req->execute();
$count = $user_req->rowCount();
// only show the frame if there is some users to validate
if ($count > 0) {
    $message = "There are users waiting for validation of their account :";
    $message .= "<form method='post' action='admin-exec.php'>";
    $message .= "<ul>";
    while ($data = $user_req->fetch()) {
        $message .= "<li><label>
            <input type='checkbox' name='validate[]' 
            value='".$data['userid']."'> ".$data['firstname']." ".$data['lastname']." (".$data['email'].")
            </label></li>";
    }
    $message .= "</ul><div class='center'>
    <button class='button' type='submit'>Validate users</button></div>";
    var_dump($message);
    display_message('error', $message);
    // as this will 'echo', we need to call it at the right moment. It will not go smoothly into $message.
    $formKey->output_formkey();
    echo "</form>";
}
?>

<div id='tabs'>
<ul>
<li><a href='#tabs-1'>Team Settings</a></li>
<li><a href='#tabs-2'>Users</a></li>
<li><a href='#tabs-3'>Status</a></li>
<li><a href='#tabs-4'>Types of items</a></li>
<li><a href='#tabs-5'>Experiment template</a></li>
<li><a href='#tabs-6'>Import CSV</a></li>
</ul>

<!-- TABS 1 -->
<div id='tabs-1'>

    <h3>TEAM SETTINGS</h3>
    <form method='post' action='admin-exec.php'>
    <div id='config_form'>
        <label for='deletable_xp'>Users can delete experiments :</label>
        <select name='deletable_xp' id='deletable_xp'>
            <option value='1'<?php
                if (get_team_config('deletable_xp') == 1) { echo " selected='selected'"; } ?>
            >yes</option>
            <option value='0'<?php
                    if (get_team_config('deletable_xp') == 0) { echo " selected='selected'"; } ?>
            >no, only the admin can</option>
        </select>
    <br />
    <br />
        <label for='link_name'>Name of the link in the main menu :</label>
        <input type='text' value='<?php echo get_team_config('link_name');?>' name='link_name' id='link_name' />
    <br />
    <br />
        <label for='link_href'>Address where this link should point :</label>
        <input type='url' value='<?php echo get_team_config('link_href');?>' name='link_href' id='link_href' />
    </div>
    <div class='center'>
        <button type='submit' name='submit_config' class='submit button'>Save</button>
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
            <button type='submit' class='button'>Edit this user</button>
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
    <h4><strong>Delete a user</strong></h4>
    <form action='admin-exec.php' method='post'>
        <!-- form key -->
        <?php $formKey->output_formkey(); ?>
        <label for'delete_user'>Type EMAIL ADDRESS of a member to delete this user and all his experiments/files forever :</label>
        <input type='email' name='delete_user' id='delete_user' />
        <br>
        <br>
        <label for'delete_user_confpass'>Type your password :</label>
        <input type='password' name='delete_user_confpass' id='delete_user_confpass' />
    <div class='center'>
        <button type='submit' class='button submit'>Delete this user !</button>
    </div>
    </form>
</section>

</div>

<!-- TABS-3 -->
<div id='tabs-3'>

    <h3>STATUS</h3>
    <?php
    // SQL to get all status
    $sql = "SELECT * from status WHERE team = :team_id";
    $req = $pdo->prepare($sql);
    $req->bindParam(':team_id', $_SESSION['team_id'], PDO::PARAM_INT);
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
            <button type='submit' class='button'>Edit <?php echo stripslashes($status['name']);?></button><br />
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
                <button type='submit' class='submit button'>Add new status</button>
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
$sql = "SELECT * from items_types WHERE team = :team";
$req = $pdo->prepare($sql);
$req->execute(array(
    'team' => $_SESSION['team_id']
));

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
                <button type='submit' class='button'>Edit <?php echo stripslashes($items_types['name']);?></button><br />
            </div>
        </div>
    </form>

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
        <button type='submit' class='button'>Add new item type</button>
        </div>
    </form>
</section>
</div>

<!-- TABS 5 -->
<div id='tabs-5'>
<?php
// get what is the default experiment template
$sql = "SELECT body FROM experiments_templates WHERE userid = 0 AND team = :team LIMIT 1";
$req = $pdo->prepare($sql);
$req->bindParam(':team', $_SESSION['team_id'], PDO::PARAM_INT);
$req->execute();
$exp_tpl = $req->fetch();
?>
    <h3>EDIT DEFAULT EXPERIMENT TEMPLATE</h3>
    <p>This is the default text when someone creates an experiment.</p>
    <form action='admin-exec.php' method='post'>
    <input type='hidden' name='default_exp_tpl' value='1' />
    <textarea class='mceditable' name='default_exp_tpl' />
<?php
echo $exp_tpl['body'];
?></textarea><br />
    <div class='center'>
    <button type='submit' class='button'>Edit default template</button>
    </div>
    </form>

</div>

<!-- TABS 6 -->
<div id='tabs-6'>
    <h3><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/import.png' alt='import' /> IMPORT CSV FILE INTO DATABASE</h3>
<?php
$row = 0;
$inserted = 0;
$column = array();

// file upload block
// show select of type
// SQL to get items names
$sql = "SELECT * FROM items_types WHERE team = :team_id";
$req = $pdo->prepare($sql);
$req->bindParam(':team_id', $_SESSION['team_id'], PDO::PARAM_INT);
$req->execute();
?>
<p style='text-align:justify'>This page will allow you to import a .csv (Excel spreadsheet) file into the database.
Firt you need to open your (.xls/.xlsx) file in Excel or Libreoffice and save it as .csv.
In order to have a good import, the first column should be the title. The rest of the columns will be imported in the body. You can make a tiny import of 3 lines to see if everything works before you import a big file.
<b>You should make a backup of your database before importing thousands of items !</b></p>

<label for='item_selector'>1. Select a type of item to import to :</label>
<select id='item_selector' onchange='goNext(this.value)'><option value=''>--------</option>
<?php
while ($items_types = $req->fetch()) {
    echo "<option value='".$items_types['id']."' name='type' ";
    echo ">".$items_types['name']."</option>";
}
?>
</select><br>
<div id='import_block'>
<form enctype="multipart/form-data" action="admin.php" method="POST">
    <label for='uploader'>2. Select a CSV file to import :</label>
    <input id='uploader' name="csvfile" type="file" />
    <br>
    <br>
    <div class='center'>
        <button type="submit" class='button' value="Upload">Import CSV</button>
    </div>
</form>
</div>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // open the file
    $handle = fopen($_FILES['csvfile']['tmp_name'], 'r');
    if ($handle == false) {
        die('Could not open the file.');
    }

    // get what type we want
    if (isset($_COOKIE['itemType']) && is_pos_int($_COOKIE['itemType'])) {
        $type = $_COOKIE['itemType'];
    }
    // loop the lines
    while ($data = fgetcsv($handle, 0, ",")) {
        $num = count($data);
        // get the column names (first line)
        if($row == 0) {
            for($i=0;$i < $num;$i++) {
                $column[] = $data[$i];
            }
            $row++;
            continue;
        }
        $row++;

        $title = $data[0];
        $body = '';
        $j = 0;
        foreach($data as $line) {
            $body .= "<p><b>".$column[$j]." :</b> ".$line.'</p>';
            $j++;
        }

        // SQL for importing
        $sql = "INSERT INTO items(team, title, date, body, userid, type) VALUES(:team, :title, :date, :body, :userid, :type)";
        $req = $pdo->prepare($sql);
        $result = $req->execute(array(
            'team' => $_SESSION['team_id'],
            'title' => $title,
            'date' => kdate(),
            'body' => $body,
            'userid' => $_SESSION['userid'],
            'type' => $type
        ));
        if ($result) {
            $inserted++;
        }
    }
    fclose($handle);
    $msg_arr[] = $inserted." items were imported successfully.";
    $_SESSION['infos'] = $msg_arr;
}
?>
<script>
function goNext(x) {
    if(x == '') {
        return;
    }
    document.cookie = 'itemType='+x;
    $('#import_block').show();
}
$(document).ready(function() {
    $('#import_block').hide();
});
</script>

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
        plugins : "table textcolor searchreplace code fullscreen insertdatetime paste charmap save image link",
        toolbar1: "undo redo | bold italic underline | fontsizeselect | alignleft aligncenter alignright alignjustify | superscript subscript | bullist numlist outdent indent | forecolor backcolor | charmap | link",
        removed_menuitems : "newdocument",
    });
});
</script>


<?php require_once 'inc/footer.php';
