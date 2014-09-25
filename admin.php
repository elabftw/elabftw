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
    die(ADMIN_DIE);
}
$page_title = ADMIN_TITLE;
require_once 'inc/head.php';
require_once 'inc/info_box.php';
// formkey stuff
require_once 'lib/classes/formkey.class.php';
$formKey = new formKey();
?>
<script src="js/tinymce/tinymce.min.js"></script>
<script src="js/raphael/raphael-min.js"></script>
<script src="js/colorwheel/colorwheel.js"></script>
<?php
// MAIN SQL FOR USERS
$sql = "SELECT * FROM users WHERE validated = :validated AND team = :team";
$user_req = $pdo->prepare($sql);
$user_req->bindValue(':validated', 0);
$user_req->bindValue(':team', $_SESSION['team_id']);
$user_req->execute(); $count = $user_req->rowCount();
// only show the frame if there is some users to validate
if ($count > 0) {
    $message = ADMIN_VALIDATION_QUEUE;
    $message .= "<form method='post' action='admin-exec.php'>";
    $message .= "<ul>";
    while ($data = $user_req->fetch()) {
        $message .= "<li><label>
            <input type='checkbox' name='validate[]' 
            value='".$data['userid']."'> ".$data['firstname']." ".$data['lastname']." (".$data['email'].")
            </label></li>";
    }
    $message .= "</ul><div class='center'>
    <button class='button' type='submit'>".ADMIN_VALIDATION_QUEUE_SUBMIT."</button></div>";
    display_message('error', $message);
    // as this will 'echo', we need to call it at the right moment. It will not go smoothly into $message.
    $formKey->output_formkey();
    echo "</form>";
}
?>

<menu>
    <ul>
        <li class='tabhandle' id='tab1'>Team</li>
        <li class='tabhandle' id='tab2'>Users</li>
        <li class='tabhandle' id='tab3'>Status</li>
        <li class='tabhandle' id='tab4'>Types of items</li>
        <li class='tabhandle' id='tab5'>Experiment template</li>
        <li class='tabhandle' id='tab6'>Import CSV</li>
    </ul>
</menu>

<!-- TABS 1 -->
<div class='divhandle' id='tab1div'>

<h3><?php echo ADMIN_TEAM_H3;?></h3>
    <form method='post' action='admin-exec.php'>
        <p>
        <label for='deletable_xp'><?php echo ADMIN_TEAM_DELETABLE_XP;?></label>
        <select name='deletable_xp' id='deletable_xp'>
            <option value='1'<?php
                if (get_team_config('deletable_xp') == 1) { echo " selected='selected'"; } ?>
                    ><?php echo YES;?></option>
            <option value='0'<?php
                    if (get_team_config('deletable_xp') == 0) { echo " selected='selected'"; } ?>
                        ><?php echo NO;?></option>
        </select>
        </p>
        <p>
        <label for='link_name'><?php echo ADMIN_TEAM_LINK_NAME;?></label>
        <input type='text' value='<?php echo get_team_config('link_name');?>' name='link_name' id='link_name' />
        </p>
        <p>
        <label for='link_href'><?php echo ADMIN_TEAM_LINK_HREF;?></label>
        <input type='url' value='<?php echo get_team_config('link_href');?>' name='link_href' id='link_href' />
        </p>
        <p>
        <label for='stamplogin'><?php echo ADMIN_TEAM_STAMPLOGIN;?></label>
        <input type='email' value='<?php echo get_team_config('stamplogin');?>' name='stamplogin' id='stamplogin' />
        <span class='smallgray'><?php echo ADMIN_TEAM_STAMPLOGIN_HELP;?></span>
        </p>
        <p>
        <label for='stamppass'><?php echo ADMIN_TEAM_STAMPPASS;?></label>
        <input type='password' value='<?php echo get_team_config('stamppass');?>' name='stamppass' id='stamppass' />
        <span class='smallgray'><?php echo ADMIN_TEAM_STAMPPASS_HELP;?></span>
        </p>
        <div class='center'>
            <button type='submit' name='submit_config' class='submit button'>Save</button>
        </div>
    </form>

</div>

<!-- TABS 2 -->
<div class='divhandle' id='tab2div'>

    <h3><?php echo ADMIN_USERS_H3;?></h3>
    <?php
    // we show only the validated users here
    $user_req->bindValue(':validated', 1);
    $user_req->execute();
    while ($users = $user_req->fetch()) {
        ?>
        <div class='simple_border'>
            <a class='trigger_users_<?php echo $users['userid'];?>'><?php echo $users['firstname']." ".$users['lastname'];?></a>
            <div class='toggle_users_<?php echo $users['userid'];?>'>
        <br>
                <form method='post' action='admin-exec.php' id='admin_user_form'>
                    <input type='hidden' value='<?php echo $users['userid'];?>' name='userid' />
                    <label class='block' for='edituser_firstname'><?php echo FIRSTNAME;?></label>
                    <input  id='edituser_firstname' type='text' value='<?php echo $users['firstname'];?>' name='firstname' />
                    <label class='block' for='edituser_lastname'><?php echo LASTNAME;?></label>
                    <input  id='edituser_lastname' type='text' value='<?php echo $users['lastname'];?>' name='lastname' />
                    <label class='block' for='edituser_username'><?php echo USERNAME;?></label>
                    <input  id='edituser_username' type='text' value='<?php echo $users['username'];?>' name='username' />
                    <label class='block' for='edituser_email'><?php echo EMAIL;?></label>
                    <input id='edituser_email' type='email' value='<?php echo $users['email'];?>' name='email' /><br>
        <br>
        <label for='validated'><?php echo ADMIN_USERS_VALIDATED;?></label>
        <select name='validated' id='validated'>
            <option value='1'<?php
                    if ($users['validated'] == 1) { echo " selected='selected'"; } ?>
                        ><?php echo YES;?></option>
            <option value='0'<?php
                if ($users['validated'] == 0) { echo " selected='selected'"; } ?>
                    ><?php echo NO;?></option>
        </select>
        <br>
        <label for='usergroup'><?php echo ADMIN_USERS_GROUP;?></label>
        <select name='usergroup' id='usergroup'>
<?php
            if ($_SESSION['is_sysadmin'] == 1) {
?>
                <option value='1'<?php
                        if ($users['usergroup'] == 1) { echo " selected='selected'"; } ?>
                >Sysadmins</option>
<?php
            }
?>
            <option value='2'<?php
                    if ($users['usergroup'] == 2) { echo " selected='selected'"; } ?>
            >Admins</option>
            <option value='3'<?php
                    if ($users['usergroup'] == 3) { echo " selected='selected'"; } ?>
            >Admin + Lock power</option>
            <option value='4'<?php
                    if ($users['usergroup'] == 4) { echo " selected='selected'"; } ?>
            >Users</option>
        </select>
        <br>
        <label for='users_reset_password'><?php echo ADMIN_USERS_RESET_PASSWORD;?></label>
        <input id='users_reset_password' type='password' value='' name='new_password' />
        <br>
        <label for='users_reset_cpassword'><?php echo ADMIN_USERS_REPEAT_PASSWORD;?></label>
        <input id='users_reset_cpassword' type='password' value='' name='confirm_new_password' />
        <br>
        <br>
        <div class='center'>
        <button type='submit' class='button'><?php echo ADMIN_USERS_BUTTON;?></button>
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

    <h3><?php echo ADMIN_DELETE_USER_H3;?></h3>
    <h4><strong><?php echo ADMIN_DELETE_USER_H4;?></strong></h4>
    <form action='admin-exec.php' method='post'>
        <!-- form key -->
        <?php $formKey->output_formkey(); ?>
        <label for='delete_user'><?php echo ADMIN_DELETE_USER_HELP;?></label>
        <input type='email' name='delete_user' id='delete_user' />
        <br>
        <br>
        <label for='delete_user_confpass'><?php echo ADMIN_DELETE_USER_CONFPASS;?></label>
        <input type='password' name='delete_user_confpass' id='delete_user_confpass' />
    <div class='center'>
        <button type='submit' class='button submit'><?php echo ADMIN_DELETE_USER_BUTTON;?></button>
    </div>
    </form>
</section>

</div>

<!-- TAB 3 -->
<div class='divhandle' id='tab3div'>
    <h3><?php echo ADMIN_STATUS_ADD_H3;?></h3>
    <p>
    <form action='admin-exec.php' method='post'>
        <label for='new_status_name'><?php echo ADMIN_STATUS_ADD_NEW;?></label>
        <input type='text' id='new_status_name' name='new_status_name' />
        <div id='colorwheel_div_new_status'>
            <div class='colorwheel inline'></div>
            <input type='text' name='new_status_color' value='#000000' />
        </div>
        <div class='center'>
            <button type='submit' class='submit button'><?php echo ADMIN_STATUS_ADD_BUTTON;?></button>
        </div>
    </form>
    </p>
    <br><br>

    <h3><?php echo ADMIN_STATUS_EDIT_H3;?></h3>

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
        $count_exp_sql = "SELECT COUNT(id) FROM experiments WHERE status = :status AND team = :team";
        $count_exp_req = $pdo->prepare($count_exp_sql);
        $count_exp_req->bindParam(':status', $status['id'], PDO::PARAM_INT);
        $count_exp_req->bindParam(':team', $_SESSION['team_id'], PDO::PARAM_INT);
        $count_exp_req->execute();
        $count = $count_exp_req->fetchColumn();
        ?>
        <div class='simple_border'>
        <a class='trigger_status_<?php echo $status['id'];?>'><?php echo $status['name'];?></a>
        <div class='toggle_container_status_<?php echo $status['id'];?>'>
        <?php
        if ($count == 0) {
            ?>
            <img class='align_right' src='img/small-trash.png' title='delete' alt='delete' onClick="deleteThis('<?php echo $status['id'];?>','status', 'admin.php')" />
        <?php
        } else {
            ?>
                <img class='align_right' src='img/small-trash.png' title='delete' alt='delete' onClick="alert(<?php echo ADMIN_STATUS_EDIT_ALERT;?>)" />
        <?php
        }
        ?>

        <form action='admin-exec.php' method='post'>
            <input type='text' name='status_name' value='<?php echo stripslashes($status['name']);?>' />
            <label for='default_checkbox'><?php echo ADMIN_STATUS_EDIT_DEFAULT;?></label>
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
            <br>

            <div class='center'>
            <button type='submit' class='button'><?php echo EDIT.' '.stripslashes($status['name']);?></button><br>
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


</div>

<!-- TAB 4 ITEMS TYPES-->
<div class='divhandle' id='tab4div'>
    <h3><?php echo ADMIN_ITEMS_TYPES_H3;?></h3>
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
            <a class='trigger_<?php echo $items_types['id'];?>'><?php echo EDIT.' '.$items_types['name'];?></a>
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
                <img class='align_right' src='img/small-trash.png' title='delete' alt='delete' onClick="deleteThis('<?php echo $items_types['id'];?>','item_type', 'admin.php')" />
            <?php
            } else {
                ?>
                <img class='align_right' src='img/small-trash.png' title='delete' alt='delete' onClick="alert(<?php echo ADMIN_ITEMS_TYPES_ALERT;?>)" />
            <?php
            }
            ?>

            <form action='admin-exec.php' method='post'>
            <label><?php echo ADMIN_ITEMS_TYPES_EDIT_NAME;?></label>
                <input type='text' name='item_type_name' value='<?php echo stripslashes($items_types['name']);?>' />
                <input type='hidden' name='item_type_id' value='<?php echo $items_types['id'];?>' />

                <div id='colorwheel_div_<?php echo $items_types['id'];?>'>
                    <div class='colorwheel inline'></div>
                    <input type='color' name='item_type_bgcolor' value='#<?php echo $items_types['bgcolor'];?>'/>
                </div><br><br>
                <textarea class='mceditable' name='item_type_template' /><?php echo stripslashes($items_types['template']);?></textarea><br>
                <div class='center'>
                    <button type='submit' class='button'><?php echo EDIT.' '.stripslashes($items_types['name']);?></button><br>
                </div>
            </div>
            </form>

        <script>$(document).ready(function() {
            $(".toggle_container_<?php echo $items_types['id'];?>").hide();
            $("a.trigger_<?php echo $items_types['id'];?>").click(function(){
                $('div.toggle_container_<?php echo $items_types['id'];?>').slideToggle(1);
            });
            color_wheel('#colorwheel_div_<?php echo $items_types['id'];?>')
        });</script>
        </div>
        <?php
    } // end generation of items_types
    ?>


    <section class='simple_border'>
        <form action='admin-exec.php' method='post'>
            <label for='new_item_type_name'><?php echo ADMIN_ITEMS_TYPES_ADD;?></label> 
            <input type='text' id='new_item_type_name' name='new_item_type_name' />
            <input type='hidden' name='new_item_type' value='1' />
            <div id='colorwheel_div_new'>
                <div class='colorwheel inline'></div>
                <input type='text' name='new_item_type_bgcolor' value='#000000' />
            </div><br><br><br><br>
            <textarea class='mceditable' name='new_item_type_template' /></textarea>
            <div class='center'>
            <button type='submit' class='button'><?php echo ADMIN_ITEMS_TYPES_ADD_BUTTON;?></button>
            </div>
        </form>
    </section>
</div>

<!-- TABS 5 -->
<div class='divhandle' id='tab5div'>

    <h3><?php echo ADMIN_EXPERIMENT_TEMPLATE_H3;?></h3>
    <?php
    // get what is the default experiment template
    $sql = "SELECT body FROM experiments_templates WHERE userid = 0 AND team = :team LIMIT 1";
    $req = $pdo->prepare($sql);
    $req->bindParam(':team', $_SESSION['team_id'], PDO::PARAM_INT);
    $req->execute();
    $exp_tpl = $req->fetch();
    ?>
    <p><?php echo ADMIN_EXPERIMENT_TEMPLATE_HELP;?></p>
    <form action='admin-exec.php' method='post'>
        <input type='hidden' name='default_exp_tpl' value='1' />
        <textarea class='mceditable' name='default_exp_tpl' />
        <?php
        echo $exp_tpl['body'];
        ?></textarea>
        <div class='center'>
        <button type='submit' class='button'><?php echo EDIT;?></button>
        </div>
    </form>
</div>

<!-- TABS 6 -->
<div class='divhandle' id='tab6div'>

    <h3><?php echo ADMIN_IMPORT_CSV_H3;?></h3>
    <?php

    // file upload block
    // show select of type
    // SQL to get items names
    $sql = "SELECT * FROM items_types WHERE team = :team_id";
    $req = $pdo->prepare($sql);
    $req->bindParam(':team_id', $_SESSION['team_id'], PDO::PARAM_INT);
    $req->execute();
    ?>
        <p style='text-align:justify'><?php echo ADMIN_IMPORT_CSV_HELP."<span class='strong'>".ADMIN_IMPORT_CSV_HELP_STRONG;?></span></p>

        <label for='item_selector'><?php echo ADMIN_IMPORT_CSV_STEP_1;?></label>
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
        <label for='uploader'><?php echo ADMIN_IMPORT_CSV_STEP_2;?></label>
            <input id='uploader' name="csvfile" type="file" />
            <div class='center'>
            <button type="submit" class='button' value="Upload"><?php echo ADMIN_IMPORT_CSV_BUTTON;?></button>
            </div>
        </form>
    </div>
</div>

<?php
// CODE TO IMPORT CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $row = 0;
    $inserted = 0;
    $column = array();
    // open the file
    $handle = fopen($_FILES['csvfile']['tmp_name'], 'r');
    if ($handle == false) {
        die('Could not open the file.');
    }

    // get what type we want
    if (isset($_COOKIE['itemType']) && is_pos_int($_COOKIE['itemType'])) {
        $type = $_COOKIE['itemType'];
    } else {
        die('No cookies found');
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
    $msg_arr[] = $inserted.' '.ADMIN_IMPORT_CSV_MSG;
    $_SESSION['infos'] = $msg_arr;
}
// END CODE TO IMPORT CSV
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

// color wheel
function color_wheel(div_name) {
        var cw = Raphael.colorwheel($(div_name)[0], 80);
            cw.input($(div_name+" input" )[0]);
}

$(document).ready(function() {
    // TABS
    // get the tab=X parameter in the url
    var params = getGetParameters();
    var tab = parseInt(params['tab']);
    if (!isInt(tab)) {
        var tab = 1;
    }
    var initdiv = '#tab' + tab + 'div';
    var inittab = '#tab' + tab;
    // init
    $(".divhandle").hide();
    $(initdiv).show();
    $(inittab).addClass('selected');

    $(".tabhandle" ).click(function(event) {
        var tabhandle = '#' + event.target.id;
        var divhandle = '#' + event.target.id + 'div';
        $(".divhandle").hide();
        $(divhandle).show();
        $(".tabhandle").removeClass('selected');
        $(tabhandle).addClass('selected');
    });
    // END TABS
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
