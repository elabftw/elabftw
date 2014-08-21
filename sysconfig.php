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
require_once 'inc/info_box.php';
// formkey stuff
require_once 'lib/classes/formkey.class.php';
$formKey = new formKey();
?>
<menu>
    <ul>
        <li class='tabhandle' id='tab1'>Teams</li>
        <li class='tabhandle' id='tab2'>Server</li>
        <li class='tabhandle' id='tab3'>Timestamp</li>
        <li class='tabhandle' id='tab4'>Security</li>
        <li class='tabhandle' id='tab5'>Email</li>
    </ul>
</menu>


<!-- TAB 1 -->
<div class='divhandle' id='tab1div'>
    <p>
    <h3>Add a new team</h3>
    <form method='post' action='admin-exec.php'>
        <input type='text' placeholder='Enter new team name' name='new_team' id='new_team' />
        <button type='submit' class='submit button'>Add</button>
    </form>
    </p>

    <p>
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
        echo " <input type='text' name='edit_team_name' value='".$team['team_name']."' id='team_".$team['team_id']."' />";
        echo " <input id='button_".$team['team_id']."' onClick=\"updateTeam('".$team['team_id']."')\" type='submit' class='button' value='Save' />";
        echo " Members: ".$count['totusers']." − Experiments: ".$count['totxp']." − Items: ".$count['totdb']." − Created: ".$team['datetime']."<br>";
    }
    ?>
    </p>
</div>

<!-- TAB 2 -->
<div class='divhandle' id='tab2div'>
    <form method='post' action='admin-exec.php'>
        <h3>Under the hood</h3>
        <label for='debug'>Activate debug mode :</label>
        <select name='debug' id='debug'>
            <option value='1'<?php
                if (get_config('debug') == 1) { echo " selected='selected'"; } ?>
            >yes</option>
            <option value='0'<?php
                    if (get_config('debug') == 0) { echo " selected='selected'"; } ?>
            >no</option>
        </select>
        <p class='smallgray'>When activated, content of $_SESSION and $_COOKIES array will be displayed in the footer for admins.</p>
        <label for='proxy'>Address of the proxy :</label>
        <input type='text' value='<?php echo get_config('proxy');?>' name='proxy' id='proxy' />
        <p class='smallgray'>If you are behind a firewall/proxy, enter the address here. Example : http://proxy.example.com:3128</p>
        <label for='path'>Full path to the install folder :</label>
        <input type='text' value='<?php echo get_config('path');?>' name='path' id='path' />
        <p class='smallgray'>This is actually the md5 hash of the path to the install. You probably don't need to change that except when you move an existing install.</p>
        <div class='center'>
            <button type='submit' name='submit_config' class='submit button'>Save</button>
        </div>
    </form>
</div>

<!-- TAB 3 -->
<div class='divhandle' id='tab3div'>
    <h3>Universign timestamping configuration</h3>
    <form method='post' action='admin-exec.php'>
        <label for='stampshare'>The teams can use the credentials below to timestamp :</label>
        <select name='stampshare' id='stampshare'>
            <option value='1'<?php
                if (get_config('stampshare') == 1) { echo " selected='selected'"; } ?>
            >yes</option>
            <option value='0'<?php
                    if (get_config('stampshare') == 0) { echo " selected='selected'"; } ?>
            >no</option>
        </select>
        <p class='smallgray'>You can control if the teams can use the global Universign account. If set to <em>no</em>, the team admin must add login infos in the admin panel.</p>
        <label for='stamplogin'>Login for external timestamping service :</label>
        <input type='email' value='<?php echo get_config('stamplogin');?>' name='stamplogin' id='stamplogin' />
        <p class='smallgray'>Must be an email address.</p>
        <label for='stamppass'>Password for external timestamping service :</label>
        <input type='password' value='<?php echo get_config('stamppass');?>' name='stamppass' id='stamppass' />
        <p class='smallgray'>This password will be stored in clear in the database ! Make sure it doesn't open other doors…</p>
        <div class='center'>
            <button type='submit' name='submit_config' class='submit button'>Save</button>
        </div>
    </form>
</div>

<!-- TAB 4 -->
<div class='divhandle' id='tab4div'>
    <h3>Security settings</h3>
    <form method='post' action='admin-exec.php'>
        <label for='admin_validate'>Users need validation by admin after registration :</label>
        <select name='admin_validate' id='admin_validate'>
            <option value='1'<?php
                if (get_config('admin_validate') == 1) { echo " selected='selected'"; } ?>
            >yes</option>
            <option value='0'<?php
                    if (get_config('admin_validate') == 0) { echo " selected='selected'"; } ?>
            >no</option>
        </select>
        <p class='smallgray'>Set to yes for added security.</p>
        <label for='login_tries'>Number of allowed login attempts :</label>
        <input type='text' value='<?php echo get_config('login_tries');?>' name='login_tries' id='login_tries' />
        <p class='smallgray'>3 might be too few. See for yourself :)</p>
        <label for='ban_time'>Time of the ban after failed login attempts (in minutes) :</label>
        <input type='text' value='<?php echo get_config('ban_time');?>' name='ban_time' id='ban_time' />
        <p class='smallgray'>To identify an user, we use an md5 of user agent + IP. Because doing it only based on IP address would surely cause problems.</p>
        <div class='center'>
            <button type='submit' name='submit_config' class='submit button'>Save</button>
        </div>
    </form>
</div>

<!-- TAB 5 -->
<div class='divhandle' id='tab5div'>
    <h3>SMTP settings</h3>
    <form method='post' action='admin-exec.php'>
        <p>Without a valid way to send emails, users won't be able to reset their password.
        It is recommended to create a specific gmail account, and add the infos here.</p>
        <p>
        <label for='smtp_address'>Address of the SMTP server :</label>
        <input type='text' value='<?php echo get_config('smtp_address');?>' name='smtp_address' id='smtp_address' />
        </p>
        <p>
        <span class='smallgray'>The default value (173.194.66.108) corresponds to smtp.gmail.com. But sometimes the gmail.com domain name is forbidden, so this is a workaround.<br>Also, it speed things up as you don't need to lookup for the IP address.</span>
        <label for='smtp_encryption'>SMTP encryption (can be TLS or STARTSSL):</label>
        <input type='text' value='<?php echo get_config('smtp_encryption');?>' name='smtp_encryption' id='smtp_encryption' />
        </p>
        <p>
        <span class='smallgray'>Gmail uses TLS.</span>
        <label for='smtp_port'>SMTP port :</label>
        <input type='text' value='<?php echo get_config('smtp_port');?>' name='smtp_port' id='smtp_port' />
        </p>
        <p>
        <span class='smallgray'>Default is 587.</span>
        <label for='smtp_username'>SMTP username :</label>
        <input type='text' value='<?php echo get_config('smtp_username');?>' name='smtp_username' id='smtp_username' />
        </p>
        <p>
        <label for='smtp_password'>SMTP password :</label>
        <input type='password' value='<?php echo get_config('smtp_password');?>' name='smtp_password' id='smtp_password' />
        </p>
        <div class='center'>
            <button type='submit' name='submit_config' class='submit button'>Save</button>
        </div>
    </form>
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
});
</script>

<?php require_once 'inc/footer.php';
