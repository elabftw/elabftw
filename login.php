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
require_once 'inc/common.php';
$page_title = _('Login');
$selected_menu = null;
require_once 'inc/head.php';

// Check if already logged in
if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1) {
    header('Location: experiments.php');
    exit;
}

$formKey = new \Elabftw\Elabftw\FormKey();

// if we are not in https, die saying we work only in https
if (!\Elabftw\Elabftw\Tools::usingSsl()) {
    // get the url to display a link to click (without the port)
    $url = 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
    $message = "eLabFTW works only in HTTPS. Please enable HTTPS on your server. Or click this link : <a href='$url'>$url</a>";
    display_message('error', $message);
    require_once 'inc/footer.php';
    exit;
}

// Check if we are banned after too much failed login attempts
$sql = "SELECT user_infos FROM banned_users WHERE time > :ban_time";
$req = $pdo->prepare($sql);
$req->execute(array(
    ':ban_time' => date("Y-m-d H:i:s", strtotime('-' . get_config('ban_time') . ' minutes'))
));
$banned_users_arr = array();
while ($banned_users = $req->fetch()) {
    $banned_users_arr[] = $banned_users['user_infos'];
}
if (in_array(md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']), $banned_users_arr)) {
    display_message('error', _('You cannot login now because of too many failed login attempts.'));
    require_once 'inc/footer.php';
    exit;
}

// show message if there is a failed_attempt
if (isset($_SESSION['failed_attempt']) && $_SESSION['failed_attempt'] < get_config('login_tries')) {
    $number_of_tries_left = get_config('login_tries') - $_SESSION['failed_attempt'];
    $message = _('Number of login attempt left before being banned for') . ' ' . get_config('ban_time') . ' ' . _('minutes:') . ' ' . $number_of_tries_left;
    display_message('error', $message);
}

// disable login if too much failed_attempts
if (isset($_SESSION['failed_attempt']) && $_SESSION['failed_attempt'] >= get_config('login_tries')) {
    // get user infos
    $user_infos = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    // add the user to the banned list
    $sql = "INSERT INTO banned_users (user_infos) VALUES (:user_infos)";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'user_infos' => $user_infos
    ));
    unset($_SESSION['failed_attempt']);
    display_message('error', _('You cannot login now because of too many failed login attempts.'));
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
    var cookie_alert = "<div class='errorbox messagebox<p><?php echo _('Please enable cookies in your navigator to continue.'); ?></p></div>";
    document.write(cookie_alert);
}
</script>

    <menu class='border' style='color:#29AEB9'><?php echo _('Note: you need cookies enabled to log in.'); ?></menu>
<section class='center loginform'>
    <!-- Login form -->
    <form id='login' method="post" action="app/login-exec.php" autocomplete="off">
        <h2><?php echo _('Sign in to your account'); ?></h2>
        <p>
        <label class='block' for="username"><?php echo _('Username'); ?></label>
            <input name="username" type="text" required /><br>
            <label class='block' for="password"><?php echo _('Password'); ?></label>
            <input name="password" type="password" required /><br>
            <!-- form key -->
            <?php echo $formKey->getFormkey(); ?>
        </p>
        <div id='loginButtonDiv'>
        <button type="submit" class='button' name="Submit"><?php echo _('Login'); ?></button>
        </div>
    </form>
    <p><?php printf(_("Don't have an account? %sRegister%s now!<br>Lost your password? %sReset%s it!"), "<a href='register.php'>", "</a>", "<a href='#' class='trigger'>", "</a>"); ?></p>
    <div class='toggle_container'>
    <form name='resetPass' method='post' action='app/reset.php'>
    <input placeholder='<?php echo _('Enter your email address'); ?>' name='email' type='email' required />
    <button class='button' type="submit" name="Submit"><?php echo _('Send new password'); ?></button>
    </form>
    </div>
</section>
<?php require_once 'inc/footer.php'; ?>
<!-- BEGIN PASSSWORD RESET FORM -->
<script>
$(document).ready(function(){
	$(".toggle_container").hide();
	$("a.trigger").click(function(){
		$('.toggle_container').slideToggle("slow");
	});
});
</script>
