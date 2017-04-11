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
    $Logs = new Logs();
    $TeamsView = new TeamsView(new Teams());
    $UsersView = new UsersView(new Users());

    try {
        // we put another try here because an exception here would end the page
        // and not getting the latest version is not a big deal
        $ReleaseCheck = new ReleaseCheck($Config);
    } catch (Exception $e) {
        echo Tools::displayMessage($e->getMessage(), 'ko');
    }

    // display current and latest version
    echo "<p>" . _('Installed version:') . " " . $ReleaseCheck::INSTALLED_VERSION . " ";
    if ($ReleaseCheck->success === true) {
        // show a little green check if we have latest version
        if (!$ReleaseCheck->updateIsAvailable()) {
            echo "<img src='app/img/check.png' width='16px' length='16px' title='latest' style='position:relative;bottom:2px' alt='OK' />";
        }
        // display latest version
        echo "<br>" . _('Latest version:') . " " . $ReleaseCheck->getLatestVersion() . "</p>";

        // if we don't have the latest version, show button redirecting to doc
        if ($ReleaseCheck->updateIsAvailable()) {
            $message = $ReleaseCheck->getReleaseDate() . " - " .
                _('A new version is available!') . " <a href='https://elabftw.readthedocs.io/en/latest/how-to-update.html'>
                <button class='button'>Update elabftw</button></a>
                <a href='" . $ReleaseCheck->getChangelogLink() . "'><button class='button'>Read changelog</button></a>";
            echo Tools::displayMessage($message, 'warning');
        }
    } else {
        echo "</p>";
    }

    if ($Config->configArr['mail_from'] === 'notconfigured@example.com') {
        $message = sprintf(_('Please finalize install : %slink to documentation%s.'), "<a href='https://elabftw.readthedocs.io/en/latest/postinstall.html#setting-up-email'>", "</a>");
        echo Tools::displayMessage($message, 'ko');
    }

    $langsArr = Tools::getLangsArr();

    switch ($Config->configArr['mail_method']) {
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
    }

    $phpInfos = array(PHP_OS, PHP_VERSION, PHP_INT_MAX, PHP_SYSCONFDIR); 

    $logsArr = $Logs->read();

    echo $twig->render('sysconfig.html', array(
        'Config' => $Config,
        'UsersView' => $UsersView,
        'TeamsView' => $TeamsView,
        'langsArr' => $langsArr,
        'disable_sendmail' => $disable_sendmail,
        'disable_smtp' => $disable_smtp,
        'disable_php' => $disable_php,
        'phpInfos' => $phpInfos,
        'logsArr' => $logsArr
    ));

} catch (Exception $e) {
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
