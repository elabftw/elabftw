<?php
/**
 * sysconfig.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Exception;

/**
 * Administrate elabftw install
 *
 */
require_once 'inc/common.php';

try {
    if ($_SESSION['is_sysadmin'] != 1) {
        throw new Exception(_('This section is out of your reach.'));
    }

    $page_title = _('eLabFTW configuration');
    $selected_menu = null;
    require_once 'inc/head.php';

    $formKey = new FormKey();
    $crypto = new CryptoWrapper();
    $SysconfigView = new SysconfigView(new Update(), new Logs(), new TeamsView());

    $stamppass = get_config('stamppass');
    $smtppass = get_config('smtp_password');

    if (strlen($stamppass) > 0) {
        $stamppass = $crypto->decrypt($stamppass);
    }
    if (strlen($smtppass) > 0) {
        $smtppass = $crypto->decrypt($smtppass);
    }

    try {
        // we put another try here because an exception here would end the page
        // and not getting the latest version is not a big deal
        $SysconfigView->Update->getUpdatesIni();
    } catch (Exception $e) {
        display_message('ko_nocross', $e->getMessage());
    }

    if ($SysconfigView->Update->success === true) {
        // display current and latest version
        echo "<br><p>" . _('Installed version:') . " " . $SysconfigView->Update->getInstalledVersion() . " ";
        // show a little green check if we have latest version
        if (!$SysconfigView->Update->updateIsAvailable()) {
            echo "<img src='img/check.png' width='16px' length='16px' title='latest' style='position:relative;bottom:8px' alt='OK' />";
        }
        // display latest version
        echo "<br>" . _('Latest version:') . " " . $SysconfigView->Update->getLatestVersion() . "</p>";

        // if we don't have the latest version, show button redirecting to wiki
        if ($SysconfigView->Update->updateIsAvailable()) {
            $message = _('A new version is available!') . " <a href='doc/_build/html/how-to-update.html'>
                <button class='button'>Update elabftw</button></a>";
            display_message('warning', $message);
        }
    }

    if (get_config('mail_from') === 'notconfigured@example.com') {
        $message = sprintf(_('Please finalize install : %slink to documentation%s.'), "<a href='doc/_build/html/postinstall.html#setting-up-email'>", "</a>");
        display_message('ko', $message);
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

    <!-- TAB 1 TEAMS -->
    <div class='divhandle' id='tab1div'>
    <div id='teamsDiv'>
        <?php
        echo $SysconfigView->TeamsView->showStats();
        echo $SysconfigView->TeamsView->showPromoteSysadmin();
        echo $SysconfigView->TeamsView->showCreate();
        echo $SysconfigView->TeamsView->show();
        ?>
    </div>
    </div>

    <!-- TAB 2 -->
    <div class='divhandle' id='tab2div'>
        <form class='box' method='post' action='app/sysconfig-exec.php'>
            <h3><?php echo _('Under the hood'); ?></h3>
            <label for='lang'><?php echo _('Language'); ?></label>
            <select id='lang' name="lang">
    <?php
    $lang_array = array('en_GB', 'ca_ES', 'de_DE', 'es_ES', 'fr_FR', 'it_IT', 'pt_BR', 'zh_CN');
    $current_lang = get_config('lang');

    foreach ($lang_array as $lang) {
        echo "<option ";
        if ($current_lang === $lang) {
            echo ' selected ';
        }
        echo "value='" . $lang . "'>" . $lang . "</option>";
    }
    ?>
            </select><br>

            <label for='proxy'><?php echo _('Address of the proxy:'); ?></label>
            <input type='text' value='<?php echo get_config('proxy'); ?>' name='proxy' id='proxy' />
            <p class='smallgray'><?php echo _('If you are behind a firewall/proxy, enter the address here. Example : http://proxy.example.com:3128'); ?></p>

            <div class='submitButtonDiv'>
                <button type='submit' name='submit_config' class='button'><?php echo _('Save'); ?></button>
            </div>
        </form>

        <div class='box'>
            <h3><?php echo _('Informations'); ?></h3>
            <ul>
                <li><p><?php echo _('Operating System') . ': ' . PHP_OS; ?></p></li>
                <li><p><?php echo _('PHP Version') . ': ' . PHP_VERSION; ?></p></li>
                <li><p><?php echo _('Size of the uploads folder') . ': ' . Tools::formatBytes(Tools::dirSize(ELAB_ROOT . 'uploads')) .
                    ' (' . Tools::dirNum(ELAB_ROOT . 'uploads') . ' files)'; ?></p></li>
                <li><p><?php echo _('Largest integer supported') . ': ' . PHP_INT_MAX; ?></p></li>
                <li><p><?php echo _('PHP configuration directory') . ': ' . PHP_SYSCONFDIR; ?></p></li>
            </ul>
        </div>
    </div>

    <!-- TAB 3 TIMESTAMP -->
    <div class='divhandle' id='tab3div'>
        <form class='box' method='post' action='app/sysconfig-exec.php'>
            <h3><?php echo _('Timestamping configuration'); ?></h3>
            <label for='stampshare'><?php echo _('The teams can use the credentials below to timestamp:'); ?></label>
            <select name='stampshare' id='stampshare'>
                <option value='1'<?php
                    if (get_config('stampshare') == 1) { echo " selected='selected'"; } ?>
                ><?php echo _('Yes'); ?></option>
                <option value='0'<?php
                        if (get_config('stampshare') == 0) { echo " selected='selected'"; } ?>
                ><?php echo _('No'); ?></option>
            </select>
            <p class='smallgray'><?php echo _('You can control if the teams can use the global timestamping account. If set to <em>no</em> the team admin must add login infos in the admin panel.'); ?></p>
            <p>
            <label for='stampprovider'><?php echo _('URL for external timestamping service:'); ?></label>
            <input type='url' placeholder='http://zeitstempel.dfn.de/' value='<?php echo get_config('stampprovider'); ?>' name='stampprovider' id='stampprovider' />
            <span class='smallgray'><?php printf(_('This should be the URL used for %sRFC 3161%s-compliant timestamping requests.'), "<a href='https://tools.ietf.org/html/rfc3161'>", "</a>"); ?></span>
            </p>
            <p>
            <label for='stampcert'><?php echo _('Chain of certificates of the external timestamping service:'); ?></label>
            <input type='text' placeholder='vendor/pki.dfn.pem' value='<?php echo get_config('stampcert'); ?>' name='stampcert' id='stampcert' />
            <span class='smallgray'><?php printf(_('This should point to the chain of certificates used by your external timestamping provider to sign the timestamps.%sLocal path relative to eLabFTW installation directory. The file needs to be in %sPEM-encoded (ASCII)%s format!'), "<br>", "<a href='https://en.wikipedia.org/wiki/Privacy-enhanced_Electronic_Mail'>", "</a>"); ?></span>
            </p>
            <label for='stamplogin'><?php echo _('Login for external timestamping service:'); ?></label>
            <input type='text' value='<?php echo get_config('stamplogin'); ?>' name='stamplogin' id='stamplogin' /><br>
            <label for='stamppass'><?php echo _('Password for external timestamping service:'); ?></label>
            <input type='password' value='<?php echo $stamppass; ?>' name='stamppass' id='stamppass' />
            <div class='submitButtonDiv'>
            <button type='submit' name='submit_config' class='button'><?php echo _('Save'); ?></button>
            </div>
        </form>
    </div>

    <!-- TAB 4 SECURITY -->
    <div class='divhandle' id='tab4div'>
        <div class='box'>
            <h3><?php echo _('Security settings'); ?></h3>
            <form method='post' action='app/sysconfig-exec.php'>
            <label for='admin_validate'><?php echo _('Users need validation by admin after registration:'); ?></label>
                <select name='admin_validate' id='admin_validate'>
                    <option value='1'<?php
                        if (get_config('admin_validate') == 1) { echo " selected='selected'"; } ?>
                    ><?php echo _('Yes'); ?></option>
                    <option value='0'<?php
                            if (get_config('admin_validate') == 0) { echo " selected='selected'"; } ?>
                    ><?php echo _('No'); ?></option>
                </select>
                <p class='smallgray'><?php echo _('Set to yes for added security.'); ?></p>
                <label for='login_tries'><?php echo _('Number of allowed login attempts:'); ?></label>
                <input type='text' value='<?php echo get_config('login_tries'); ?>' name='login_tries' id='login_tries' />
                <p class='smallgray'><?php echo _('3 might be too few. See for yourself :)'); ?></p>
                <label for='ban_time'><?php echo _('Time of the ban after failed login attempts (in minutes:'); ?></label>
                <input type='text' value='<?php echo get_config('ban_time'); ?>' name='ban_time' id='ban_time' />
                <p class='smallgray'><?php echo _('To identify an user we use an md5 of user agent + IP. Because doing it only based on IP address would surely cause problems.'); ?></p>
                <div class='submitButtonDiv'>
                    <button type='submit' name='submit_config' class='button'><?php echo _('Save'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 5 -->
    <div class='divhandle' id='tab5div'>
        <div class='box'>
            <h3><?php echo _('E-mail settings'); ?></h3>
    <?php
    $mail_method = get_config('mail_method');
    switch ($mail_method) {
        case 'sendmail':
            $disable_sendmail = false;
            $disable_smtp = true;
            $disable_php = true;
            break;
        case 'smtp':
            $disable_sendmail = true;
            $disable_smtp = false;
            $disable_php = true;
            break;
        case 'php':
            $disable_sendmail = true;
            $disable_smtp = true;
            $disable_php = false;
            break;
        default:
            $disable_sendmail = true;
            $disable_smtp = true;
            $disable_php = true;
    } ?>
        <form method='post' action='app/sysconfig-exec.php'>
            <p><?php echo _("Without a valid way to send emails users won't be able to reset their password. It is recommended to create a specific Mailgun.com (or gmail account and add the infos here."); ?></p>
            <p>
            <label for='mail_method'><?php echo _('Send e-mails via:'); ?></label>
            <select onchange='toggleMailMethod($("#toggle_main_method").val())' name='mail_method' id='toggle_main_method'>
                <option value=''><?php echo _('Select mailing method...'); ?></option>
                <option value='sendmail' <?php if (!$disable_sendmail) {
        echo 'selected="selected"';
    }
    ?>><?php echo _('Local MTA (default)'); ?></option>
                <option value='smtp' <?php if (!$disable_smtp) {
        echo 'selected="selected"';
    }
    ?>><?php echo _('SMTP'); ?></option>
                <option value='php' <?php if (!$disable_php) {
        echo 'selected="selected"';
    }
    ?>><?php echo _('PHP'); ?></option>
            </select>
            </p>
            <div id='general_mail_config'>
                <p>
                <label for='mail_from'><?php echo _('Sender address:'); ?></label>
                <input type='text' value='<?php echo get_config('mail_from'); ?>' name='mail_from' id='mail_from' />
                </p>
            </div>
            <div id='sendmail_config'>
                <p>
                <label for='sendmail_path'><?php echo _('Path to sendmail:'); ?></label>
                <input type='text' placeholder='/usr/bin/sendmail' value='<?php echo get_config('sendmail_path'); ?>' name='sendmail_path' id='sendmail_path' />
                </p>
            </div>
            <div id='smtp_config'>
                <p>
                <label for='smtp_address'><?php echo _('Address of the SMTP server:'); ?></label>
                <input type='text' value='<?php echo get_config('smtp_address'); ?>' name='smtp_address' id='smtp_address' />
                </p>
                <p>
                <span class='smallgray'>smtp.mailgun.com</span>
                <label for='smtp_encryption'><?php echo _('SMTP encryption (can be TLS or STARTSSL):'); ?></label>
                <input type='text' value='<?php echo get_config('smtp_encryption'); ?>' name='smtp_encryption' id='smtp_encryption' />
                </p>
                <p>
                <span class='smallgray'><?php echo _('Probably TLS'); ?></span>
                <label for='smtp_port'><?php echo _('SMTP Port:'); ?></label>
                <input type='text' value='<?php echo get_config('smtp_port'); ?>' name='smtp_port' id='smtp_port' />
                </p>
                <p>
                <span class='smallgray'><?php echo _('Default is 587.'); ?></span>
                <label for='smtp_username'><?php echo _('SMTP username:'); ?></label>
                <input type='text' value='<?php echo get_config('smtp_username'); ?>' name='smtp_username' id='smtp_username' />
                </p>
                <p>
                <label for='smtp_password'><?php echo _('SMTP password'); ?></label>
                <input type='password' value='<?php echo $smtppass; ?>' name='smtp_password' id='smtp_password' />
                </p>
                </div>
                <div class='submitButtonDiv'>
                    <button type='submit' name='submit_config' class='button'><?php echo _('Save'); ?></button>
                </div>
            </form>
        </div>

        <!-- TEST EMAIL -->
        <?php echo $SysconfigView->testemailShow(); ?>

    </div>

    <!-- TAB 6 LOGS -->
    <div class='divhandle' id='tab6div'>
        <?php echo $SysconfigView->logsShow(); ?>
    </div>

    <script>
    $(document).ready(function() {
        // we need to add this otherwise the button will stay disabled with the browser's cache (Firefox)
        var input_list = document.getElementsByTagName('input');
        for (var i=0; i < input_list.length; i++) {
            var input = input_list[i];
            input.disabled = false;
        }
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
        // honor already saved mail_method setting and hide unused options accordingly
        toggleMailMethod(<?php echo json_encode($mail_method); ?>);
    });
    </script>
    <?php
} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
    display_message('ko', $e->getMessage());
} finally {
    require_once 'inc/footer.php';
}
