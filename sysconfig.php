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
require_once 'lang/'.$_SESSION['prefs']['lang'].'.php';
if ($_SESSION['is_sysadmin'] != 1) {
    die(NO_ACCESS_DIE);
}
$page_title = SYSCONFIG_TITLE;
$selected_menu = null;
require_once 'inc/head.php';
require_once 'inc/info_box.php';
// formkey stuff
require_once 'lib/classes/formkey.class.php';
$formKey = new formKey();
?>
<menu>
    <ul>
        <li class='tabhandle' id='tab1'><?php echo SYSCONFIG_TEAMS;?></li>
        <li class='tabhandle' id='tab2'><?php echo SYSCONFIG_SERVER;?></li>
        <li class='tabhandle' id='tab3'><?php echo SYSCONFIG_TIMESTAMP;?></li>
        <li class='tabhandle' id='tab4'><?php echo SYSCONFIG_SECURITY;?></li>
        <li class='tabhandle' id='tab5'><?php echo EMAIL;?></li>
    </ul>
</menu>

<!-- TAB 1 -->
<div class='divhandle' id='tab1div'>
    <p>
    <h3><?php echo SYSCONFIG_1_H3_1;?></h3>
    <form method='post' action='admin-exec.php'>
        <input type='text' placeholder='Enter new team name' name='new_team' id='new_team' />
        <button type='submit' class='submit button'>Add</button>
    </form>
    </p>

    <p>
    <h3><?php echo SYSCONFIG_1_H3_2;?></h3>
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
        echo SYSCONFIG_MEMBERS.": ".$count['totusers']." − ".EXPERIMENTS_TITLE.": ".$count['totxp']." − ".SYSCONFIG_ITEMS.": ".$count['totdb']." − ".SYSCONFIG_CREATED.": ".$team['datetime']."<br>";
    }
    ?>
    </p>
</div>

<!-- TAB 2 -->
<div class='divhandle' id='tab2div'>
    <form method='post' action='admin-exec.php'>
        <h3><?php echo LANGUAGE;?></h3>
            <select id='lang' name="lang">
                <option
                <?php
                if (get_config('lang') === 'en-GB') {
                    echo ' selected ';}?>value="en-GB">en-GB</option>
                <option
                <?php
                if (get_config('lang') === 'fr-FR') {
                    echo ' selected ';}?>value="fr-FR">fr-FR</option>
            </select>
        <h3><?php echo SYSCONFIG_2_H3;?></h3>
        <label for='debug'><?php echo SYSCONFIG_DEBUG;?></label>
        <select name='debug' id='debug'>
            <option value='1'<?php
                if (get_config('debug') == 1) { echo " selected='selected'"; } ?>
            ><?php echo YES;?></option>
            <option value='0'<?php
                    if (get_config('debug') == 0) { echo " selected='selected'"; } ?>
            ><?php echo NO;?></option>
        </select>
        <p class='smallgray'><?php echo SYSCONFIG_DEBUG_HELP;?></p>
        <label for='proxy'><?php echo SYSCONFIG_PROXY;?></label>
        <input type='text' value='<?php echo get_config('proxy');?>' name='proxy' id='proxy' />
        <p class='smallgray'><?php echo SYSCONFIG_PROXY_HELP;?></p>
        <label for='path'><?php echo SYSCONFIG_PATH;?></label>
        <input type='text' value='<?php echo get_config('path');?>' name='path' id='path' />
        <p class='smallgray'><?php echo SYSCONFIG_PATH_HELP;?></p>
        <div class='center'>
            <button type='submit' name='submit_config' class='submit button'><?php echo SAVE;?></button>
        </div>
    </form>
</div>

<!-- TAB 3 -->
<div class='divhandle' id='tab3div'>
    <h3><?php echo SYSCONFIG_3_H3;?></h3>
    <form method='post' action='admin-exec.php'>
        <label for='stampshare'><?php echo SYSCONFIG_STAMPSHARE;?></label>
        <select name='stampshare' id='stampshare'>
            <option value='1'<?php
                if (get_config('stampshare') == 1) { echo " selected='selected'"; } ?>
            ><?php echo YES;?></option>
            <option value='0'<?php
                    if (get_config('stampshare') == 0) { echo " selected='selected'"; } ?>
            ><?php echo NO;?></option>
        </select>
        <p class='smallgray'><?php echo SYSCONFIG_STAMPSHARE_HELP;?></p>
        <label for='stamplogin'><?php echo SYSCONFIG_STAMPLOGIN_HELP;?></label>
        <input type='email' value='<?php echo get_config('stamplogin');?>' name='stamplogin' id='stamplogin' />
        <p class='smallgray'><?php echo SYSCONFIG_STAMPLOGIN_HELP;?></p>
        <label for='stamppass'><?php echo SYSCONFIG_STAMPPASS;?></label>
        <input type='password' value='<?php echo get_config('stamppass');?>' name='stamppass' id='stamppass' />
        <p class='smallgray'><?php echo SYSCONFIG_STAMPPASS_HELP;?></p>
        <div class='center'>
        <button type='submit' name='submit_config' class='submit button'><?php echo SAVE;?></button>
        </div>
    </form>
</div>

<!-- TAB 4 -->
<div class='divhandle' id='tab4div'>
    <h3><?php echo SYSCONFIG_4_H3;?></h3>
    <form method='post' action='admin-exec.php'>
    <label for='admin_validate'><?php echo SYSCONFIG_ADMIN_VALIDATE;?></label>
        <select name='admin_validate' id='admin_validate'>
            <option value='1'<?php
                if (get_config('admin_validate') == 1) { echo " selected='selected'"; } ?>
            ><?php echo YES;?></option>
            <option value='0'<?php
                    if (get_config('admin_validate') == 0) { echo " selected='selected'"; } ?>
            ><?php echo NO;?></option>
        </select>
        <p class='smallgray'><?php echo SYSCONFIG_ADMIN_VALIDATE_HELP;?></p>
        <label for='login_tries'><?php echo SYSCONFIG_LOGIN_TRIES;?></label>
        <input type='text' value='<?php echo get_config('login_tries');?>' name='login_tries' id='login_tries' />
        <p class='smallgray'><?php echo SYSCONFIG_LOGIN_TRIES_HELP;?></p>
        <label for='ban_time'><?php echo SYSCONFIG_BAN_TIME;?></label>
        <input type='text' value='<?php echo get_config('ban_time');?>' name='ban_time' id='ban_time' />
        <p class='smallgray'><?php echo SYSCONFIG_BAN_TIME_HELP;?></p>
        <div class='center'>
            <button type='submit' name='submit_config' class='submit button'><?php echo SAVE;?></button>
        </div>
    </form>
</div>

<!-- TAB 5 -->
<div class='divhandle' id='tab5div'>
    <h3><?php echo SYSCONFIG_5_H3;?></h3>
    <form method='post' action='admin-exec.php'>
        <p><?php echo SYSCONFIG_5_HELP;?></p>
        <p>
        <label for='smtp_address'><?php echo SYSCONFIG_SMTP_ADDRESS;?></label>
        <input type='text' value='<?php echo get_config('smtp_address');?>' name='smtp_address' id='smtp_address' />
        </p>
        <p>
        <span class='smallgray'><?php echo SYSCONFIG_SMTP_ADDRESS_HELP;?></span>
        <label for='smtp_encryption'><?php echo SYSCONFIG_SMTP_ENCRYPTION;?></label>
        <input type='text' value='<?php echo get_config('smtp_encryption');?>' name='smtp_encryption' id='smtp_encryption' />
        </p>
        <p>
        <span class='smallgray'><?php echo SYSCONFIG_SMTP_ENCRYPTION_HELP;?></span>
        <label for='smtp_port'><?php echo SYSCONFIG_SMTP_PORT;?></label>
        <input type='text' value='<?php echo get_config('smtp_port');?>' name='smtp_port' id='smtp_port' />
        </p>
        <p>
        <span class='smallgray'><?php echo SYSCONFIG_SMTP_PORT_HELP;?></span>
        <label for='smtp_username'><?php echo SYSCONFIG_SMTP_USERNAME;?></label>
        <input type='text' value='<?php echo get_config('smtp_username');?>' name='smtp_username' id='smtp_username' />
        </p>
        <p>
        <label for='smtp_password'><?php echo SYSCONFIG_SMTP_PASSWORD;?></label>
        <input type='password' value='<?php echo get_config('smtp_password');?>' name='smtp_password' id='smtp_password' />
        </p>
        <div class='center'>
            <button type='submit' name='submit_config' class='submit button'><?php echo SAVE;?></button>
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
        document.getElementById('button_'+team_id).value = '<?php echo SAVED?>';
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
