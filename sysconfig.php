<?php
/**
 * sysconfig.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Exception;

/**
 * Administrate elabftw install
 *
 */

try {
    require_once 'app/init.inc.php';
    $pageTitle = _('eLabFTW Configuration');
    $selectedMenu = null;
    require_once 'app/head.inc.php';

    if ($_SESSION['is_sysadmin'] != 1) {
        throw new Exception(_('This section is out of your reach.'));
    }

    $Config = new Config();
    $SysconfigView = new SysconfigView(new Update($Config), new Logs(), new TeamsView(new Teams()));
    $UsersView = new UsersView(new Users());

    try {
        // we put another try here because an exception here would end the page
        // and not getting the latest version is not a big deal
        $SysconfigView->Update->getUpdatesIni();
    } catch (Exception $e) {
        echo Tools::displayMessage($e->getMessage(), 'ko');
    }

    // display current and latest version
    echo "<p>" . _('Installed version:') . " " . $SysconfigView->Update->getInstalledVersion() . " ";
    if ($SysconfigView->Update->success === true) {
        // show a little green check if we have latest version
        if (!$SysconfigView->Update->updateIsAvailable()) {
            echo "<img src='app/img/check.png' width='16px' length='16px' title='latest' style='position:relative;bottom:2px' alt='OK' />";
        }
        // display latest version
        echo "<br>" . _('Latest version:') . " " . $SysconfigView->Update->getLatestVersion() . "</p>";

        // if we don't have the latest version, show button redirecting to doc
        if ($SysconfigView->Update->updateIsAvailable()) {
            $message = $SysconfigView->Update->getReleaseDate() . " - " .
                _('A new version is available!') . " <a href='https://elabftw.readthedocs.io/en/latest/how-to-update.html'>
                <button class='button'>Update elabftw</button></a>
                <a href='" . $SysconfigView->Update->getChangelogLink() . "'><button class='button'>Read changelog</button></a>";
            echo Tools::displayMessage($message, 'warning');
        }
    } else {
        echo "</p>";
    }

    if ($Config->configArr['mail_from'] === 'notconfigured@example.com') {
        $message = sprintf(_('Please finalize install : %slink to documentation%s.'), "<a href='https://elabftw.readthedocs.io/en/latest/postinstall.html#setting-up-email'>", "</a>");
        echo Tools::displayMessage($message, 'ko');
    }
    ?>

    <menu>
        <ul>
            <li class='tabhandle' id='tab1'><?= _('Teams') ?></li>
            <li class='tabhandle' id='tab2'><?= _('Users') ?></li>
            <li class='tabhandle' id='tab3'><?= _('Server') ?></li>
            <li class='tabhandle' id='tab4'><?= _('Timestamp') ?></li>
            <li class='tabhandle' id='tab5'><?= _('Security') ?></li>
            <li class='tabhandle' id='tab6'><?= _('Email') ?></li>
            <li class='tabhandle' id='tab7'><?= _('Logs') ?></li>
        </ul>
    </menu>

    <!-- TAB 1 TEAMS -->
    <div class='divhandle' id='tab1div'>
        <div id='teamsDiv'>
            <div class='box'>
                <h3><?= _('Usage Statistics') ?></h3>
                <hr>
                <p><?= $SysconfigView->TeamsView->showStats() ?></p>
            </div>
            <?php
            echo $SysconfigView->TeamsView->showPromoteSysadmin();
            echo $SysconfigView->TeamsView->showCreate();
            echo $SysconfigView->TeamsView->show();
            echo $SysconfigView->TeamsView->showMassEmail();
            ?>
        </div>
    </div>

    <!-- TAB 2 USERS -->
    <div class='divhandle' id='tab2div'>
        <?= $UsersView->showEditUsers() ?>
    </div>

    <!-- TAB 3 SERVER -->
    <div class='divhandle' id='tab3div'>
        <form class='box' method='post' action='app/controllers/SysconfigController.php'>
            <h3><?= _('Under the Hood') ?></h3>
            <hr>
            <input type='hidden' name='updateConfig' value='true' />
            <label class="block" for='lang'><?= _('Language') ?></label>
            <select id='lang' name="lang" class="clean-form col-3-form">
    <?php
    $langsArr = Tools::getLangsArr();
    $current_lang = $Config->configArr['lang'];

    foreach ($langsArr as $lang => $text) {
        echo "<option ";
        if ($current_lang === $lang) {
            echo ' selected ';
        }
        echo "value='" . $lang . "'>" . $text . "</option>";
    }
    ?>
            </select><br>

            <label class="block" for='proxy'><?= _('Address of the Proxy:') ?></label>
            <input class="clean-form col-3-form" type='text' value='<?= $Config->configArr['proxy'] ?>' name='proxy' id='proxy' />
            <p class='smallgray'><?= _('If you are behind a firewall/proxy, enter the address here. Example : http://proxy.example.com:3128') ?></p>

            <div class='submitButtonDiv'>
                <button type='submit' class='button'><?= _('Save') ?></button>
            </div>
        </form>

        <div class='box'>
            <h3><?= _('Informations') ?></h3>
            <hr>
            <ul class="clean-list">
                <li><p><?= _('Operating System') . ': ' . PHP_OS ?></p></li>
                <li><p><?= _('PHP Version') . ': ' . PHP_VERSION ?></p></li>
                <li><p><?= _('Largest integer supported') . ': ' . PHP_INT_MAX ?></p></li>
                <li><p><?= _('PHP configuration directory') . ': ' . PHP_SYSCONFDIR ?></p></li>
            </ul>
        </div>
    </div>

    <!-- TAB 4 TIMESTAMP -->
    <div class='divhandle' id='tab4div'>
        <form class='box' method='post' action='app/controllers/SysconfigController.php'>
            <h3><?php echo _('Timestamping Configuration'); ?></h3>
            <hr>
            <input type='hidden' name='updateConfig' value='true' />
            <label for='stampshare'><?php echo _('The teams can use the credentials below to timestamp:'); ?></label>
            <select class="clean-form" name='stampshare' id='stampshare'>
                <option value='1'
                    <?= $Config->configArr['stampshare'] ? " selected='selected'" : "" ?>
                ><?= _('Yes') ?></option>
                <option value='0'
                    <?= !$Config->configArr['stampshare'] ? " selected='selected'" : "" ?>
                ><?= _('No'); ?></option>
            </select>
            <p class='smallgray'><?= _('You can control if the teams can use the global timestamping account. If set to <em>no</em> the team admin must add login infos in the admin panel.') ?></p>
            <p>
            <label class="block" for='stampprovider'><?= _('URL for external timestamping service:') ?></label>
            <input class="clean-form col-3-form" type='url' placeholder='http://zeitstempel.dfn.de/' value='<?= $Config->configArr['stampprovider'] ?>' name='stampprovider' id='stampprovider' />
            <span class='smallgray'><?php printf(_('This should be the URL used for %sRFC 3161%s-compliant timestamping requests.'), "<a href='https://tools.ietf.org/html/rfc3161'>", "</a>"); ?></span>
            </p>
            <p>
            <label class="block" for='stampcert'><?= _('Chain of certificates of the external timestamping service:'); ?></label>
            <input class="clean-form col-3-form" type='text' placeholder='app/dfn-cert/pki.dfn.pem' value='<?= $Config->configArr['stampcert'] ?>' name='stampcert' id='stampcert' />
            <span class='smallgray'><?php printf(_("This should point to the chain of certificates used by your external timestamping provider to sign the timestamps.%sLocal path relative to eLabFTW installation directory. You should add it in the 'uploads' folder. The file needs to be in %sPEM-encoded (ASCII)%s format!"), "<br>", "<a href='https://en.wikipedia.org/wiki/Privacy-enhanced_Electronic_Mail'>", "</a>"); ?></span>
            </p>
            <label class="block" for='stamplogin'><?= _('Login for external timestamping service:') ?></label>
            <input class="clean-form col-3-form" autocomplete='off' type='text' value='<?= $Config->configArr['stamplogin']; ?>' name='stamplogin' id='stamplogin' /><br>
            <label class="block" for='stamppass'><?= _('Password for external timestamping service:') ?></label>
    <?php
    if (strlen($Config->configArr['stamppass']) > 1) {
        echo "<p>A password is already set. ";
        echo "<a href='app/controllers/SysconfigController.php?clearStamppass=1'>Clear it</a> or change it below:</p>";
    }
    ?>
            <input class='clean-form col-3-form' autocomplete='off' type='password' name='stamppass' id='stamppass' />
            <div class='submitButtonDiv'>
                <button type='submit' class='button'><?= _('Save') ?></button>
            </div>
        </form>
    </div>

    <!-- TAB 5 SECURITY -->
    <div class='divhandle' id='tab5div'>
        <div class='box'>
            <h3><?= _('Security Settings') ?></h3>
            <hr>
            <form method='post' action='app/controllers/SysconfigController.php'>
                <input type='hidden' name='updateConfig' value='true' />

                <label for='admin_validate'><?= _('Users need validation by admin after registration:') ?></label>
                <select class="clean-form" name='admin_validate' id='admin_validate'>
                    <option value='1'
                        <?= $Config->configArr['admin_validate'] ? " selected='selected'" : "" ?>
                    ><?= _('Yes'); ?></option>
                    <option value='0'
                        <?= !$Config->configArr['admin_validate'] ? " selected='selected'" : "" ?>
                    ><?= _('No'); ?></option>
                </select>
                <p class='smallgray'><?= _('Set to yes for added security.') ?></p>
                <label class="block" for='login_tries'><?= _('Number of allowed login attempts:') ?></label>
                <input class="clean-form col-3-form" type='number' value='<?= $Config->configArr['login_tries'] ?>' name='login_tries' id='login_tries' />
                <p class='smallgray'><?= _('3 might be too few. See for yourself :)') ?></p>
                <label class="block" for='ban_time'><?= _('Time of the ban after failed login attempts (in minutes):') ?></label>
                <input class="clean-form col-3-form" type='number' value='<?= $Config->configArr['ban_time'] ?>' name='ban_time' id='ban_time' />
                <p class='smallgray'>
                    <?= _('To identify an user we use an md5 of user agent + IP. Because doing it only based on IP address would surely cause problems.'); ?>
                </p>
                <div class='submitButtonDiv'>
                    <button type='submit' class='button'><?= _('Save') ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 6 EMAIL -->
    <div class='divhandle' id='tab6div'>
        <div class='box'>
            <h3><?= _('E-mail Settings') ?></h3>
            <hr>
    <?php
    $mail_method = $Config->configArr['mail_method'];
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
        <form method='post' action='app/controllers/SysconfigController.php'>
            <input type='hidden' name='updateConfig' value='true' />


            <p><?= _("Without a valid way to send emails users won't be able to reset their password. It is recommended to create a specific smtp2go.com account and add the infos here.") ?></p>
            <p>
            <label class="block" for='mail_method'><?= _('Send e-mails via:') ?></label>
            <select class="clean-form col-3-form" onchange='toggleMailMethod($("#toggle_main_method").val())' name='mail_method' id='toggle_main_method'>
                <option value=''><?= _('Select mailing method...') ?></option>

                <option value="sendmail"<?= !$disable_sendmail ? " selected='selected'>" : ">" ?>
                <?= _('Local MTA (default)') ?></option>

                <option value="smtp"<?= !$disable_smtp ? " selected='selected'>" : ">" ?>
                <?= _('SMTP') ?></option>

                <option value="php"<?= !$disable_php ? " selected='selected'>" : ">" ?>
                <?= _('PHP') ?></option>

            </select>
            </p>

            <div id='general_mail_config'>
                <p>
                <label class="block" for='mail_from'><?= _('Sender address:') ?></label>
                <input class="clean-form col-3-form" type='text' value='<?= $Config->configArr['mail_from'] ?>' name='mail_from' id='mail_from' />
                </p>
            </div>
            <div id='sendmail_config'>
                <p>
                <label class="block" for='sendmail_path'><?= _('Path to sendmail:') ?></label>
                <input class="clean-form col-3-form" type='text' placeholder='/usr/bin/sendmail' value='<?= $Config->configArr['sendmail_path'] ?>' name='sendmail_path' id='sendmail_path' />
                </p>
            </div>
            <div id='smtp_config'>
                <p>
                <label class="block" for='smtp_address'><?= _('Address of the SMTP server:') ?></label>
                <input class="clean-form col-3-form" type='text' value='<?= $Config->configArr['smtp_address'] ?>' name='smtp_address' id='smtp_address' />
                <span class='smallgray'>mail.smtp2go.com</span>
                <label class="block" for='smtp_encryption'><?= _('SMTP encryption:') ?></label>
                <?php $smtp_encryption = $Config->configArr['smtp_encryption'] ?>
                <select class="clean-form col-3-form" name='smtp_encryption'>
                <option value='none'
                <?= $smtp_encryption === 'none' ? ' selected>' : '>' ?>
                None</option>
                <option value='tls'
                <?= $smtp_encryption === 'tls' ? ' selected>' : '>' ?>
                TLS</option>
                <option value='startssl'
                <?= $smtp_encryption === 'startssl' ? ' selected>' : '>' ?>
                STARTSSL</option>
                </select>
                <span class='smallgray'><?= _('Probably TLS') ?></span>
                <label class="block" for='smtp_port'><?= _('SMTP Port:') ?></label>
                <input class="clean-form col-3-form" type='text' value='<?= $Config->configArr['smtp_port'] ?>' name='smtp_port' id='smtp_port' />
                <span class='smallgray'><?= _('Default is 587.') ?></span>
                <label class="block" for='smtp_username'><?= _('SMTP username:') ?></label>
                <input class="clean-form col-3-form" type='text' value='<?= $Config->configArr['smtp_username'] ?>' name='smtp_username' id='smtp_username' />
                <label class="block" for='smtp_password'><?= _('SMTP password') ?></label>
                <?php
    if (strlen($Config->configArr['smtp_password']) === 0) {
        echo "<input class='clean-form col-3-form' type='password' name='smtp_password' id='smtp_password' />";
    } else {
        echo _('A password is set.');
        echo "<span class='button' id='editSmtpPassword'>" . _('Edit') . "</span>";
        echo "<input class='clean-form col-3-form' type='password' name='smtp_password' style='display:none' id='hidden_smtp_password' />";
    }
    ?>
                </p>
                </div>
                <div class='submitButtonDiv'>
                    <button type='submit' class='button'><?= _('Save') ?></button>
                </div>
            </form>
        </div>

        <!-- TEST EMAIL -->
        <?= $SysconfigView->testemailShow() ?>

    </div>

    <!-- TAB 7 LOGS -->
    <div class='divhandle' id='tab7div'>
        <?= $SysconfigView->logsShow() ?>
    </div>

    <script>
    $(document).ready(function() {
        $('#editSmtpPassword').click(function() {
            $('#hidden_smtp_password').toggle();
        });
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
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
