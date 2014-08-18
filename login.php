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
if (!isset($_SESSION)) {
    session_start();
}
$page_title = 'Login';
require_once 'inc/head.php';
require_once 'inc/connect.php';
require_once 'inc/functions.php';
require_once 'inc/info_box.php';
// formkey stuff
require_once('lib/classes/formkey.class.php');
$formKey = new formKey();

// Check for HTTPS
if (!isset($_SERVER['HTTPS'])) {
    // get the url to display a link to click (without the port)
    $url = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $message = "eLabFTW works only in HTTPS. Please enable HTTPS on your server
        (<a href='https://github.com/NicolasCARPi/elabftw/wiki/Troubleshooting#wiki-switch-to-https'
        >see documentation</a>). Or click this link : <a href='$url'>$url</a>";
    display_message('error', $message);
    require_once 'inc/footer.php';
    die();
}

// Check if already logged in
if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1) {
    $message = 'You are already logged in !';
    display_message('error', $message);
    require_once 'inc/footer.php';
    die();
}

// Check if we are banned after too much failed login attempts
$sql = "SELECT user_infos FROM banned_users WHERE time > :ban_time";
$req = $pdo->prepare($sql);
$req->execute(array(
    ':ban_time' => date("Y-m-d H:i:s", strtotime('-'.get_config('ban_time').' minutes'))
));
$banned_users_arr = array();
while ($banned_users = $req->fetch()) {
    $banned_users_arr[] = $banned_users['user_infos'];
}
if (in_array(md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']), $banned_users_arr)) {
    $message ='You cannot login now because of too much failed login attempts.';
    display_message('error', $message);
    require_once 'inc/footer.php';
    die();
}

// show message if there is a failed_attempt
if (isset($_SESSION['failed_attempt']) && $_SESSION['failed_attempt'] < get_config('login_tries')) {
    $number_of_tries_left = get_config('login_tries') - $_SESSION['failed_attempt'];
    $message = "Number of login attempt left before being banned for ".get_config('ban_time')." minutes : $number_of_tries_left.";
    display_message('error', $message);
}

// disable login if too much failed_attempts
if (isset($_SESSION['failed_attempt']) && $_SESSION['failed_attempt'] >= get_config('login_tries')) {
    // get user infos
    $user_infos = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
    // add the user to the banned list
    $sql = "INSERT INTO banned_users (user_infos) VALUES (:user_infos)";
    $req = $pdo->prepare($sql);
    $req->execute(array(
        'user_infos' => $user_infos
    ));
    unset($_SESSION['failed_attempt']);
    $message ='Too much failed login attempts. Login is disabled for '.get_config('ban_time').' minutes.';
    display_message('error', $message);
    require_once 'inc/footer.php';
    die();
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
    var cookie_alert = "<div class='ui-state-error ui-corner-all' style='margin:5px'><p><span class='ui-icon ui-icon-alert' style='float:left; margin: 0 5px 0 5px;'></span>Please enable cookies in your navigator to continue.</p></div>";
    document.write(cookie_alert);
}
</script>

<section class='center'>
    <!-- Login form -->
    <form method="post" action="login-exec.php" autocomplete="off">
        <!-- form key -->
        <?php $formKey->output_formkey(); ?>
        <fieldset>
                <p>
                    <label for="username">Username</label>
                    <input name="username" type="text" id="username" />
                </p>
                <p>
                    <label for="password">Password</label>
                    <input name="password" type="password" id="password" />
                </p>
          <button type="submit" class='button' name="Submit">Log in</button>
        </fieldset>
    </form>
    <p>Note : you need cookies enabled to log in.<br />
    Don't have an account ? <a href='register.php'>Register</a> now !<br />
    Lost your password ? <a href='#' class='trigger'>Reset</a> it !</p>
    <div class='toggle_container'>
<hr>
    <form name='resetPass' method='post' action='reset-pass.php'>
    <input placeholder='Enter your email address' name='email' type='email' />
    <button type="submit" name="Submit">Send new password</button>
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

