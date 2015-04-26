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
require_once 'vendor/autoload.php';

$crypto = new \Elabftw\Elabftw\Crypto();

$formKey = new \Elabftw\Elabftw\FormKey();

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
        <li class='tabhandle' id='tab1'><?php echo _('Teams'); ?></li>
        <li class='tabhandle' id='tab2'><?php echo _('Server'); ?></li>
        <li class='tabhandle' id='tab3'><?php echo _('Timestamp'); ?></li>
        <li class='tabhandle' id='tab4'><?php echo _('Security'); ?></li>
        <li class='tabhandle' id='tab5'><?php echo _('Email'); ?></li>
        <li class='tabhandle' id='tab6'><?php echo _('Logs'); ?></li>
    </ul>
</menu>

<div class='divhandle' id='tab1div'>
    <p>
    <h3><?php echo _('Add a new team'); ?></h3>
    <form method='post' action='app/admin-exec.php'>
        <div class="row">
            <div class="col-xs-3">
                <input type='text' placeholder='Enter new team name' name='new_team' id='new_team' class="form-control" required />
            </div>
            <div class="col-xs-3">
                <button type='submit' class='btn btn-elab'>Add</button>
            </div>
        </div>
    </form>
    <br /><br />
    </p>
    <p>
    <h3><?php echo _('Edit existing teams'); ?></h3>
    <?php
    // a lil' bit of stats can't hurt
    $count_sql = "SELECT
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
        echo "
        <div class='row'>
            <div class='col-xs-3'>
                <input type='text' class='form-control' name='edit_team_name' value='" . $team['team_name'] . "' id='team_" . $team['team_id'] . "' />
            </div>
            <div class='col-xs-3'>
                <input id='button_" . $team['team_id'] . "' onClick=\"updateTeam('" . $team['team_id'] . "')\" type='submit' class='btn btn-elab' value='Save' />
            </div>
        </div>
        ";
        echo "<p>" . _('Members') . ": " . $count['totusers'] . " − " . ngettext('Experiment', 'Experiments', $count['totxp']) . ": " . $count['totxp'] . " − " . _('Items') . ": " . $count['totdb'] . " − " . _('Created') . ": " . $team['datetime'] . "</p><br />";
    }
    ?>
    </p>
</div>

<!-- TAB 2 -->
<div class='divhandle' id='tab2div'>
    <form method='post' action='app/admin-exec.php' class="form-inline">
        <h3><?php echo _('Language'); ?></h3>
            <div class="form-group">
                <select id='lang' name="lang" class="form-control">
                    <option
                    <?php
                    if (get_config('lang') === 'en_GB') {
                        echo ' selected '; }?>value="en_GB">en_GB</option>
                    <option
                    <?php
                    if (get_config('lang') === 'ca_ES') {
                        echo ' selected '; }?>value="ca_ES">ca_ES</option>
                    <option
                    <?php
                    if (get_config('lang') === 'de_DE') {
                        echo ' selected '; }?>value="de_DE">de_DE</option>
                    <option
                    <?php
                    if (get_config('lang') === 'es_ES') {
                        echo ' selected '; }?>value="es_ES">es_ES</option>
                    <option
                    <?php
                    if (get_config('lang') === 'fr_FR') {
                        echo ' selected '; }?>value="fr_FR">fr_FR</option>
                    <option
                    <?php
                    if (get_config('lang') === 'it_IT') {
                        echo ' selected '; }?>value="it_IT">it_IT</option>
                    <option
                    <?php
                    if (get_config('lang') === 'pt_BR') {
                        echo ' selected '; }?>value="pt_BR">pt_BR</option>
                    <option
                    <?php
                    if (get_config('lang') === 'zh_CN') {
                        echo ' selected '; }?>value="zh_CN">zh_CN</option>
                </select>
            </div>
        <br /> <br />
        <h3><?php echo _('Under the hood'); ?></h3>
        <div class="form-group">
            <label for='debug'><?php echo _('Activate debug mode:'); ?></label>
            <select name='debug' id='debug' class="form-control">
                <option value='1'<?php
                    if (get_config('debug') == 1) { echo " selected='selected'"; } ?>
                ><?php echo _('Yes'); ?></option>
                <option value='0'<?php
                        if (get_config('debug') == 0) { echo " selected='selected'"; } ?>
                ><?php echo _('No'); ?></option>
            </select>
            <p class='smallgray'><?php echo _('Content of SESSION and COOKIES array will be displayed in the footer for admins.'); ?></p>
        </div>
        <br /><br />
        <div class="form-group">
            <label for='proxy'><?php echo _('Address of the proxy:'); ?></label>
            <input type='text' value='<?php echo get_config('proxy'); ?>' name='proxy' id='proxy' class="form-control" />
            <p class='smallgray'><?php echo _('If you are behind a firewall/proxy, enter the address here. Example : http://proxy.example.com:3128'); ?></p>
        </div>
        <br /><br />
        <div class='center'>
            <button type='submit' name='submit_config' class='btn btn-elab btn-lg'><?php echo _('Save'); ?></button>
        </div>
    </form>
</div>

<!-- TAB 3 -->
<div class='divhandle' id='tab3div'>
    <h3><?php echo _('Timestamping configuration'); ?></h3>
    <form method='post' action='app/admin-exec.php' class="form-inline">
        <div class="form-group">
            <label for='stampshare'><?php echo _('The teams can use the credentials below to timestamp:'); ?></label>
            <select name='stampshare' id='stampshare' class="form-control">
                <option value='1'<?php
                    if (get_config('stampshare') == 1) { echo " selected='selected'"; } ?>
                ><?php echo _('Yes'); ?></option>
                <option value='0'<?php
                        if (get_config('stampshare') == 0) { echo " selected='selected'"; } ?>
                ><?php echo _('No'); ?></option>
            </select>
        </div>
        <p class='smallgray'><?php echo _('You can control if the teams can use the global timestamping account. If set to <em>no</em> the team admin must add login infos in the admin panel.'); ?></p>
        <br />
        <div class="form-group">
            <label for='stampprovider'><?php echo _('URL for external timestamping service:');?></label>
            <input type='url' placeholder='https://ws.universign.eu/tsa' value='<?php echo get_config('stampprovider');?>' class="form-control" name='stampprovider' id='stampprovider' />
        </div>
        <p class='smallgray'><?php printf(_('This should be the URL used for %sRFC 3161%s-compliant timestamping requests.'), "<a href='https://tools.ietf.org/html/rfc3161'>", "</a>"); ?></p>
        <br />
        <div class="form-group">
            <label for='stampcert'><?php echo _('Chain of certificates of the external timestamping service:');?></label>
            <input type='text' placeholder='vendor/universign-tsa-root.pem' value='<?php echo get_config('stampcert');?>' class="form-control" name='stampcert' id='stampcert' />
        </div>
        <p class='smallgray'><?php printf(_('This should point to the chain of certificates used by your external timestamping provider to sign the timestamps.%sLocal path relative to eLabFTW installation directory. The file needs to be in %sPEM-encoded (ASCII)%s format!'), "<br>", "<a href='https://en.wikipedia.org/wiki/Privacy-enhanced_Electronic_Mail'>", "</a>"); ?></p>
        <br />
        <div class="form-group">
            <label for='stamplogin'><?php echo _('Login for external timestamping service:'); ?></label>
            <input type='text' value='<?php echo get_config('stamplogin'); ?>' name='stamplogin' id='stamplogin' class="form-control" />
            <p class='smallgray'><?php echo _('Login for external timestamping service .'); ?></p>
        </div>
        <br />
        <div class="form-group">
            <label for='stamppass'><?php echo _('Password for external timestamping service:'); ?></label>
            <input type='password' value='<?php echo $crypto->decrypt(get_config('stamppass')); ?>' name='stamppass' id='stamppass'  class="form-control" />
        </div>
        <div class='center'>
        <button type='submit' name='submit_config' class='btn btn-elab btn-lg'><?php echo _('Save'); ?></button>
        </div>
    </form>
</div>

<!-- TAB 4 -->
<div class='divhandle' id='tab4div'>
    <h3><?php echo _('Security settings'); ?></h3>
    <form method='post' action='app/admin-exec.php' class="form-inline">
        <div class="form-group">
            <label for='admin_validate'><?php echo _('Users need validation by admin after registration:'); ?></label>
            <select name='admin_validate' id='admin_validate' class="form-control">
                <option value='1'<?php
                    if (get_config('admin_validate') == 1) { echo " selected='selected'"; } ?>
                ><?php echo _('Yes'); ?></option>
                <option value='0'<?php
                        if (get_config('admin_validate') == 0) { echo " selected='selected'"; } ?>
                ><?php echo _('No'); ?></option>
            </select>
            <p class='smallgray'><?php echo _('Set to yes for added security.'); ?></p>
        </div>
        <br /><br />
        <div class="form-group">
            <label for='login_tries'><?php echo _('Number of allowed login attempts:'); ?></label>
            <input type='text' value='<?php echo get_config('login_tries'); ?>' name='login_tries' id='login_tries' class="form-control" />
            <p class='smallgray'><?php echo _('3 might be too few. See for yourself :)'); ?></p>
        </div>
        <br /><br />
        <div class="form-group">
            <label for='ban_time'><?php echo _('Time of the ban after failed login attempts (in minutes:'); ?></label>
            <input type='text' value='<?php echo get_config('ban_time'); ?>' name='ban_time' id='ban_time' class="form-control" />
            <p class='smallgray'><?php echo _('To identify an user we use an md5 of user agent + IP. Because doing it only based on IP address would surely cause problems.'); ?></p>
        </div>
        <br /><br />
        <div class='center'>
            <button type='submit' name='submit_config' class='btn btn-elab btn-lg'><?php echo _('Save'); ?></button>
        </div>
    </form>
</div>

<!-- TAB 5 -->
<div class='divhandle' id='tab5div'>
    <h3><?php echo _('SMTP settings'); ?></h3>
    <form method='post' action='app/admin-exec.php'>
        <p><?php echo _("Without a valid way to send emails users won't be able to reset their password. It is recommended to create a specific Mandrill.com (or gmail account and add the infos here."); ?></p>
        <br /><br />
        <div class="row">
            <div class="col-xs-3 txtright">
                <label for='smtp_address'><?php echo _('Address of the SMTP server:'); ?></label>
            </div>
            <div class="col-xs-2">
                <input type='text' value='<?php echo get_config('smtp_address'); ?>' class="form-control" name='smtp_address' id='smtp_address' />
            </div>
            <div class="col-xs-7">
                <span class='smallgray'>smtp.mandrillapp.com</span>
            </div>
        </div>
        <br />
        <div class="row">
            <div class="col-xs-3 txtright">
                <label for='smtp_encryption'><?php echo _('SMTP encryption (can be TLS or STARTSSL):'); ?></label>
            </div>
            <div class="col-xs-2">
                <input type='text' value='<?php echo get_config('smtp_encryption'); ?>' class="form-control" name='smtp_encryption' id='smtp_encryption' />
            </div>
            <div class="col-xs-7">
                <span class='smallgray'><?php echo _('Probably TLS'); ?></span>
            </div>
        </div>
        <br />
        <div class="row">
            <div class="col-xs-3 txtright">
                <label for='smtp_port'><?php echo _('SMTP Port:'); ?></label>
            </div>
            <div class="col-xs-2">
                <input type='text' value='<?php echo get_config('smtp_port'); ?>' class="form-control" name='smtp_port' id='smtp_port' />
            </div>
            <div class="col-xs-7">
                <span class='smallgray'><?php echo _('Default is 587.'); ?></span>
            </div>
        </div>
        <br />
        <div class="row">
            <div class="col-xs-3 txtright">
                <label for='smtp_username'><?php echo _('SMTP username:'); ?></label>
            </div>
            <div class="col-xs-2">
                <input type='text' value='<?php echo get_config('smtp_username'); ?>' class="form-control" name='smtp_username' id='smtp_username' />
            </div>
        </div>
        <br />
        <div class="row">
            <div class="col-xs-3 txtright">
                <label for='smtp_password'><?php echo _('SMTP password'); ?></label>
            </div>
            <div class="col-xs-2">
                <input type='password' value='<?php echo $crypto->decrypt(get_config('smtp_password')); ?>' class="form-control" name='smtp_password' id='smtp_password' />
            </div>
        </div>
        <br /><br />
        <div class='center'>
            <button type='submit' name='submit_config' class='btn btn-elab btn-lg'><?php echo _('Save'); ?></button>
        </div>
    </form>
</div>

<!-- TAB 6 -->
<div class='divhandle logs_div' id='tab6div'>
    <div class='well'>
        <ul>
        <?php
        $sql = "SELECT * FROM logs ORDER BY id DESC LIMIT 100";
        $req = $pdo->prepare($sql);
        $req->execute();
        while ($logs = $req->fetch()) {
            echo "<li>" . $logs['datetime'] . " [" . $logs['type'] . "] " . $logs['body'] . " (" . $logs['user'] . ")</li>";
        }
        ?>
        </ul>
    </div>
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
