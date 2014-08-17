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
/* sysconfig.php - configuration system */
require_once 'inc/common.php';
if ($_SESSION['is_sysadmin'] != 1) {
    die('You are not Sysadmin !');
}
$page_title = 'eLabFTW configuration';
require_once 'inc/head.php';
require_once 'inc/menu.php';
require_once 'inc/info_box.php';
// formkey stuff
require_once 'lib/classes/formkey.class.php';
$formKey = new formKey();
?>
<div class='item'>
    <form method='post' action='admin-exec.php'>
    <h3>Server settings</h3>
    <!-- form key -->
    <?php $formKey->output_formkey(); ?>
    <div class='config_form'>
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
        <label for='path'>Full path to the install folder :</label>
        <input type='text' value='<?php echo get_config('path');?>' name='path' id='path' />
    <br />
    <br />
        <label for='proxy'>Address of the proxy :</label>
        <input type='text' value='<?php echo get_config('proxy');?>' name='proxy' id='proxy' />
    <br />
    <br />
        <label for='stampshare'>The teams can use the credentials below to timestamp :</label>
        <select name='stampshare' id='stampshare'>
            <option value='1'<?php
                if (get_config('stampshare') == 1) { echo " selected='selected'"; } ?>
            >yes</option>
            <option value='0'<?php
                    if (get_config('stampshare') == 0) { echo " selected='selected'"; } ?>
            >no</option>
        </select>
    <br />
    <br />
        <label for='stamplogin'>Login for external timestamping service :</label>
        <input type='email' value='<?php echo get_config('stamplogin');?>' name='stamplogin' id='stamplogin' />
    <br />
    <br />
        <label for='stamppass'>Password for external timestamping service :</label>
        <input type='password' value='<?php echo get_config('stamppass');?>' name='stamppass' id='stamppass' />
    <br />
    <br />
    <h3>Security settings</h3>
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
        <label for='login_tries'>Number of allowed login attempts :</label>
        <input type='text' value='<?php echo get_config('login_tries');?>' name='login_tries' id='login_tries' />
    <br />
    <br />
        <label for='ban_time'>Time of the ban after failed login attempts (in minutes) :</label>
        <input type='text' value='<?php echo get_config('ban_time');?>' name='ban_time' id='ban_time' />
    <br />
    <br />
    <h3>Email settings</h3>
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
    </div>
    <div class='center'>
        <button type='submit' name='submit_config' class='submit button'>Save</button>
    </div>
    </form>

</div>

<hr class='flourishes'>
<h2>TEAMS</h2>
<div class='item'>
    <form method='post' action='admin-exec.php'>
        <h3>Add a new team</h3>
        <input type='text' class='biginput' name='new_team' id='new_team' />
        <div class='center'>
            <button type='submit' class='submit button'>Add</button>
        </div>
    </form>
</div>

<div class='item'>
    <h3>Edit existing teams</h3>
    <?php
    // a lil' bit of stats can't hurt
    $count_sql="SELECT
    (SELECT COUNT(users.userid) FROM users WHERE users.team = :team) AS totusers,
    (SELECT COUNT(items.id) FROM items WHERE items.team = :team) AS totdb,
    (SELECT COUNT(experiments.id) FROM experiments WHERE experiments.team = :team) AS totxp";
    $count_req = $pdo->prepare($count_sql);

    $sql = "SELECT * FROM teams";
    $req = $pdo->prepare($sql);

    $req->execute();

    while ($team = $req->fetch()) {
        $count_req->bindParam(':team', $team['team_id']);
        $count_req->execute();
        $count = $count_req->fetch(PDO::FETCH_NAMED);
        echo "<label for='team_".$team['team_id']."'>".$team['team_id']."</label>";
        echo " <input type='text' name='edit_team_name' value='".$team['team_name']."' id='team_".$team['team_id']."' />";
        echo " <input id='button_".$team['team_id']."' onClick=\"updateTeam('".$team['team_id']."')\" type='submit' value='Save' />";
        echo " Members: ".$count['totusers']." − Experiments: ".$count['totxp']." − Items: ".$count['totdb']." − Created: ".$team['datetime']."<br>";
    }
    ?>
</div>
<script>
// we need to add this otherwise the button will stay disabled with the browser's cache (Firefox)
var input_list = document.getElementsByTagName('input');
for (var i=0; i < input_list.length; i++) {
    var input = input_list[i];
    input.disabled = false;
}

function updateTeam(team_id) {
    var new_team_name = document.getElementById('team_'+team_id).value;
    console.log(new_team_name);
    var jqxhr = $.ajax({
        type: "POST",
        url: "quicksave.php",
        data: {
        id : team_id,
        team_name : new_team_name,
        }
    }).done(function() {
        document.getElementById('button_'+team_id).value = 'Saved!';
        document.getElementById('button_'+team_id).disabled = true;
    });
}
</script>

<?php require_once 'inc/footer.php';
