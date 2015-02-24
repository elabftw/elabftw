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
require_once 'inc/locale.php';
if ($_SESSION['is_sysadmin'] != 1) {
    die(_('This section is out of your reach.'));
}
$page_title = _('eLabFTW configuration');
$selected_menu = null;
require_once 'inc/head.php';
require_once 'inc/info_box.php';

// formkey stuff
require_once 'inc/classes/formkey.class.php';
$formKey = new formKey();

if (strlen(get_config('smtp_username')) == 0) {
    $message = sprintf(_('Please finalize install : %slink to documentation%s.'), "<a href='https://github.com/elabftw/elabftw/wiki/finalizing'>", "</a>");
    display_message('error', $message);
}
?>

<?php
// get current version
$current_version = shell_exec('git describe --abbrev=0 --tags');
// FIXME
// TODO
// we disable this because it's too alpha for now
if ($current_version == 'something') {
    ?>
    <div class='align_right'>
    <form method='post' action='app/admin-exec.php'>
    <input type='hidden' value='1' name='update' />
    <button type='submit' class='submit button'>Update elabftw</button>
    </form>
    </div>
<?php
}
?>

<menu>
    <ul>
        <li class='tabhandle' id='tab1'><?php echo _('Teams');?></li>
        <li class='tabhandle' id='tab2'><?php echo _('Server');?></li>
        <li class='tabhandle' id='tab3'><?php echo _('Timestamp');?></li>
        <li class='tabhandle' id='tab4'><?php echo _('Security');?></li>
        <li class='tabhandle' id='tab5'><?php echo _('Email');?></li>
    </ul>
</menu>

<div class='divhandle' id='tab1div'>
    <p>
    <h3><?php echo _('Add a new team');?></h3>
    <form method='post' action='app/admin-exec.php'>
        <input required type='text' placeholder='Enter new team name' name='new_team' id='new_team' />
        <button type='submit' class='submit button'>Add</button>
    </form>
    </p>

    <p>
    <h3><?php echo _('Edit existing teams');?></h3>
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
        echo "<p>"._('Members').": ".$count['totusers']." − ".ngettext('Experiment', 'Experiments', $count['totxp'] ).": ".$count['totxp']." − "._('Items').": ".$count['totdb']." − "._('Created').": ".$team['datetime']."<p>";
    }
    ?>
    </p>
</div>

<!-- TAB 2 -->
<div class='divhandle' id='tab2div'>
    <form method='post' action='app/admin-exec.php'>
        <h3><?php echo _('Language');?></h3>
            <select id='lang' name="lang">
                <option
                <?php
                if (get_config('lang') === 'en_GB') {
                    echo ' selected ';}?>value="en_GB">en_GB</option>
                <option
                <?php
                if (get_config('lang') === 'ca_ES') {
                    echo ' selected ';}?>value="ca_ES">ca_ES</option>
                <option
                <?php
                if (get_config('lang') === 'de_DE') {
                    echo ' selected ';}?>value="de_DE">de_DE</option>
                <option
                <?php
                if (get_config('lang') === 'es_ES') {
                    echo ' selected ';}?>value="es_ES">es_ES</option>
                <option
                <?php
                if (get_config('lang') === 'fr_FR') {
                    echo ' selected ';}?>value="fr_FR">fr_FR</option>
                <option
                <?php
                if (get_config('lang') === 'it_IT') {
                    echo ' selected ';}?>value="it_IT">it_IT</option>
                <option
                <?php
                if (get_config('lang') === 'pt_BR') {
                    echo ' selected ';}?>value="pt_BR">pt_BR</option>
                <option
                <?php
                if (get_config('lang') === 'zh_CN') {
                    echo ' selected ';}?>value="zh_CN">zh_CN</option>
            </select>
        <h3><?php echo _('Under the hood');?></h3>
        <label for='debug'><?php echo _('Activate debug mode:');?></label>
        <select name='debug' id='debug'>
            <option value='1'<?php
                if (get_config('debug') == 1) { echo " selected='selected'"; } ?>
            ><?php echo _('Yes');?></option>
            <option value='0'<?php
                    if (get_config('debug') == 0) { echo " selected='selected'"; } ?>
            ><?php echo _('No');?></option>
        </select>
        <p class='smallgray'><?php echo _('Content of SESSION and COOKIES array will be displayed in the footer for admins.');?></p>
        <label for='proxy'><?php echo _('Address of the proxy:');?></label>
        <input type='text' value='<?php echo get_config('proxy');?>' name='proxy' id='proxy' />
        <p class='smallgray'><?php echo _('If you are behind a firewall/proxy, enter the address here. Example : http://proxy.example.com:3128');?></p>
        <div class='center'>
            <button type='submit' name='submit_config' class='submit button'><?php echo _('Save');?></button>
        </div>
    </form>
</div>

<!-- TAB 3 -->
<div class='divhandle' id='tab3div'>
    <h3><?php echo _('Universign timestamping configuration');?></h3>
    <form method='post' action='app/admin-exec.php'>
        <label for='stampshare'><?php echo _('The teams can use the credentials below to timestamp:');?></label>
        <select name='stampshare' id='stampshare'>
            <option value='1'<?php
                if (get_config('stampshare') == 1) { echo " selected='selected'"; } ?>
            ><?php echo _('Yes');?></option>
            <option value='0'<?php
                    if (get_config('stampshare') == 0) { echo " selected='selected'"; } ?>
            ><?php echo _('No');?></option>
        </select>
        <p class='smallgray'><?php echo _('You can control if the teams can use the global timestamping account. If set to <em>no</em> the team admin must add login infos in the admin panel.');?></p>
        <p>
        <label for='ts_provider_url'><?php echo _('URL for external timestamping service:');?></label>
        <input type='url' value='<?php echo get_config('ts_provider_url');?>' name='ts_provider_url' id='ts_provider_url' />
        <span class='smallgray'><?php echo _('This should be the URL used for <a href="https://tools.ietf.org/html/rfc3616">RFC 3616</a>-compliant timestamping requests.');?></span>
        </p>
        <label for='stamplogin'><?php echo _('Login for external timestamping service:');?></label>
        <input type='email' value='<?php echo get_config('stamplogin');?>' name='stamplogin' id='stamplogin' />
        <p class='smallgray'><?php echo _('Login for external timestamping service .');?></p>
        <label for='stamppass'><?php echo _('Password for external timestamping service:');?></label>
        <input type='password' value='<?php echo get_config('stamppass');?>' name='stamppass' id='stamppass' />
        <p class='smallgray'><?php echo _("This password will be stored in clear in the database ! Make sure it doesn't open other doors…");?></p>
        <div class='center'>
        <button type='submit' name='submit_config' class='submit button'><?php echo _('Save');?></button>
        </div>
    </form>
</div>

<!-- TAB 4 -->
<div class='divhandle' id='tab4div'>
    <h3><?php echo _('Security settings');?></h3>
    <form method='post' action='app/admin-exec.php'>
    <label for='admin_validate'><?php echo _('Users need validation by admin after registration:');?></label>
        <select name='admin_validate' id='admin_validate'>
            <option value='1'<?php
                if (get_config('admin_validate') == 1) { echo " selected='selected'"; } ?>
            ><?php echo _('Yes');?></option>
            <option value='0'<?php
                    if (get_config('admin_validate') == 0) { echo " selected='selected'"; } ?>
            ><?php echo _('No');?></option>
        </select>
        <p class='smallgray'><?php echo _('Set to yes for added security.');?></p>
        <label for='login_tries'><?php echo _('Number of allowed login attempts:');?></label>
        <input type='text' value='<?php echo get_config('login_tries');?>' name='login_tries' id='login_tries' />
        <p class='smallgray'><?php echo _('3 might be too few. See for yourself :)');?></p>
        <label for='ban_time'><?php echo _('Time of the ban after failed login attempts (in minutes:');?></label>
        <input type='text' value='<?php echo get_config('ban_time');?>' name='ban_time' id='ban_time' />
        <p class='smallgray'><?php echo _('To identify an user we use an md5 of user agent + IP. Because doing it only based on IP address would surely cause problems.');?></p>
        <div class='center'>
            <button type='submit' name='submit_config' class='submit button'><?php echo _('Save');?></button>
        </div>
    </form>
</div>

<!-- TAB 5 -->
<div class='divhandle' id='tab5div'>
    <h3><?php echo _('SMTP settings');?></h3>
    <form method='post' action='app/admin-exec.php'>
        <p><?php echo _("Without a valid way to send emails users won't be able to reset their password. It is recommended to create a specific Mandrill.com (or gmail account and add the infos here.");?></p>
        <p>
        <label for='smtp_address'><?php echo _('Address of the SMTP server:');?></label>
        <input type='text' value='<?php echo get_config('smtp_address');?>' name='smtp_address' id='smtp_address' />
        </p>
        <p>
        <span class='smallgray'>smtp.mandrillapp.com</span>
        <label for='smtp_encryption'><?php echo _('SMTP encryption (can be TLS or STARTSSL):');?></label>
        <input type='text' value='<?php echo get_config('smtp_encryption');?>' name='smtp_encryption' id='smtp_encryption' />
        </p>
        <p>
        <span class='smallgray'><?php echo _('Probably TLS');?></span>
        <label for='smtp_port'><?php echo _('SMTP Port:');?></label>
        <input type='text' value='<?php echo get_config('smtp_port');?>' name='smtp_port' id='smtp_port' />
        </p>
        <p>
        <span class='smallgray'><?php echo _('Default is 587.');?></span>
        <label for='smtp_username'><?php echo _('SMTP username:');?></label>
        <input type='text' value='<?php echo get_config('smtp_username');?>' name='smtp_username' id='smtp_username' />
        </p>
        <p>
        <label for='smtp_password'><?php echo _('SMTP password');?></label>
        <input type='password' value='<?php echo get_config('smtp_password');?>' name='smtp_password' id='smtp_password' />
        </p>
        <div class='center'>
            <button type='submit' name='submit_config' class='submit button'><?php echo _('Save');?></button>
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
    var jqxhr = $.ajax({
        type: "POST",
        url: "app/quicksave.php",
        data: {
        id : team_id,
        team_name : new_team_name,
        }
    }).done(function() {
        document.getElementById('button_'+team_id).value = '<?php echo _('Saved')?>';
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
