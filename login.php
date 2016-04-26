<?php
/**
 * login.php
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
 * Login page
 *
 */
require_once 'inc/common.php';
$page_title = _('Login');
$selected_menu = null;

// Check if already logged in
if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1) {
    header('Location: experiments.php');
    exit;
}

require_once 'inc/head.php';

$formKey = new FormKey();
$BannedUsers = new BannedUsers();

try {
    // if we are not in https, die saying we work only in https
    if (!Tools::usingSsl()) {
        // get the url to display a link to click (without the port)
        $url = 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
        $message = "eLabFTW works only in HTTPS. Please enable HTTPS on your server. Or click this link : <a href='$url'>$url</a>";
        throw new Exception($message);
    }

    // Check if we are banned after too much failed login attempts
    if (in_array(md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']), $BannedUsers->readAll())) {
        throw new Exception(_('You cannot login now because of too many failed login attempts.'));
    }

    // show message if there is a failed_attempt
    if (isset($_SESSION['failed_attempt']) && $_SESSION['failed_attempt'] < get_config('login_tries')) {
        $number_of_tries_left = get_config('login_tries') - $_SESSION['failed_attempt'];
        $message = _('Number of login attempt left before being banned for') . ' ' . get_config('ban_time') . ' ' . _('minutes:') . ' ' . $number_of_tries_left;
        display_message('ko', $message);
    }

    // disable login if too much failed_attempts
    if (isset($_SESSION['failed_attempt']) && $_SESSION['failed_attempt'] >= get_config('login_tries')) {
        // get user infos
        $fingerprint = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        // add the user to the banned list
        $BannedUsers->create($fingerprint);

        unset($_SESSION['failed_attempt']);
        throw new Exception(_('You cannot login now because of too many failed login attempts.'));
    }

} catch (Exception $e) {
    display_message('ko', $e->getMessage());
    require_once 'inc/footer.php';
    exit;
}
?>

<script>
// Check for cookies
function checkCookiesEnabled() {
    var cookieEnabled = (navigator.cookieEnabled) ? true : false;
    if (typeof navigator.cookieEnabled == "undefined" && !cookieEnabled) {
        document.cookie="testcookie";
        cookieEnabled = (document.cookie.indexOf("testcookie") != -1) ? true : false;
    }
return (cookieEnabled);
}
if (!checkCookiesEnabled()) {
    var cookie_alert = "<div class='errorbox messagebox<p><?= _('Please enable cookies in your navigator to continue.') ?></p></div>";
    document.write(cookie_alert);
}
</script>

<menu class='border' style='color:#29AEB9'><?= _('Note: you need cookies enabled to log in.') ?></menu>
<section class='center'>
    <!-- Login form , the id is for an acceptance test -->
    <form method="post" id='login' action="app/login-exec.php" autocomplete="off">
        <h2><?= _('Sign in to your account') ?></h2>
        <p>
        <label class='block' for="email"><?= _('Email') ?></label>
        <input name="email" type="email" value='<?php
            // put the email in the field if we just registered
            if (isset($_SESSION['email'])) {
                echo $_SESSION['email'];
            }
            ?>' required /><br>
            <label class='block' for="password"><?= _('Password') ?></label>
            <input name="password" type="password" required /><br>
            <!-- form key -->
            <?= $formKey->getFormkey() ?>
        <br>
        <label for='rememberme'><?= _('Remember me') ?></label>
        <input type='checkbox' name='rememberme' id='rememberme' />
        </p>
        <div id='loginButtonDiv'>
        <button type="submit" class='button' name="Submit"><?= _('Login') ?></button>
        </div>
    </form>
    <p><?php printf(_("Don't have an account? %sRegister%s now!<br>Lost your password? %sReset%s it!"), "<a href='register.php'>", "</a>", "<a href='#' class='trigger'>", "</a>"); ?></p>
    <div class='toggle_container'>
    <form name='resetPass' method='post' action='app/reset.php'>
    <input placeholder='<?= _('Enter your email address') ?>' name='email' type='email' required />
    <button class='button' type="submit" name="Submit"><?= _('Send new password') ?></button>
    </form>
    </div>
</section>

<script>
$(document).ready(function(){
	$(".toggle_container").hide();
	$("a.trigger").click(function(){
		$('.toggle_container').slideToggle("slow");
	});
});
</script>

<?php require_once 'inc/footer.php';
