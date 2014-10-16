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
require_once 'inc/connect.php';
require_once 'inc/functions.php';
require_once 'lang/'.get_config('lang').'.php';
$page_title = LOGIN;
$selected_menu = null;
require_once 'inc/head.php';
require_once 'inc/info_box.php';
// formkey stuff
require_once('inc/classes/formkey.class.php');
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
    exit;
}

// Check if already logged in
if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1) {
    header('Location: experiments.php');
    exit;
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
    display_message('error', LOGIN_TOO_MUCH_FAILED);
    require_once 'inc/footer.php';
    exit;
}

// show message if there is a failed_attempt
if (isset($_SESSION['failed_attempt']) && $_SESSION['failed_attempt'] < get_config('login_tries')) {
    $number_of_tries_left = get_config('login_tries') - $_SESSION['failed_attempt'];
    $message = LOGIN_ATTEMPT_NB.' '.get_config('ban_time').' '.LOGIN_MINUTES.' '.$number_of_tries_left;
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
    display_message('error', LOGIN_TOO_MUCH_FAILED);
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
    var cookie_alert = "<div class='errorbox messagebox<p><?php echo LOGIN_ENABLE_COOKIES;?></p></div>";
    document.write(cookie_alert);
}
</script>

    <menu class='border' style='color:#29AEB9'><?php echo LOGIN_COOKIES_NOTE;?></menu>
<section class='center loginform'>
    <!-- Login form -->
    <form method="post" action="login-exec.php" autocomplete="off">
        <h2><?php echo LOGIN_H2;?></h2>
        <p>
        <label class='block' for="username"><?php echo USERNAME;?></label>
            <input name="username" type="text" required /><br>
            <label class='block' for="password"><?php echo PASSWORD;?></label>
            <input name="password" type="password" required /><br>
            <!-- form key -->
            <?php $formKey->output_formkey(); ?>
        </p>
        <div id='loginButtonDiv'>
        <button type="submit" class='button' name="Submit"><?php echo LOGIN;?></button>
        </div>
    </form>
    <p><?php echo LOGIN_FOOTER;?></p>
    <div class='toggle_container'>
    <form name='resetPass' method='post' action='reset-pass.php'>
    <input placeholder='<?php echo LOGIN_FOOTER_PLACEHOLDER;?>' name='email' type='email' required />
    <button class='button' type="submit" name="Submit"><?php echo LOGIN_FOOTER_BUTTON;?></button>
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
