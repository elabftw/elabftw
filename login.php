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
if (!isset($_SESSION)) { session_start(); }
$page_title = 'Login';
require_once('inc/head.php');
require_once('inc/menu.php');
require_once('inc/info_box.php');
// formkey stuff
require_once('lib/classes/formkey.class.php');
$formKey = new formKey();

print_r($_SESSION);
// anti flood stuff
// if there was less than 5 seconds between the last request and this one
if (isset($_SESSION['last_request_time']) && $_SESSION['last_request_time'] > (time() - 5)) {
    // add a counter if it's not here
    if (!isset($_SESSION['last_request_count'])) {
       $_SESSION['last_request_count'] = 1;
    // otherwise add 1 to the counter if it's less than 5
    } elseif($_SESSION['last_request_count'] < 5) {
       $_SESSION['last_request_count'] += 1;
    } else {
       $message = 'Flood detected !';
       display_message('error', $message);
       require_once('inc/footer.php');
       die();
    }
} else {
   $_SESSION['last_request_count'] = 1;
}

$_SESSION['last_request_time'] = time();

// Check if already logged in
if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1) {
    $message ='You are already logged in !';
    display_message('error', $message);
    require_once('inc/footer.php');
    die();
}
?>

<script>
// Check if user accepts cookies
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
            <legend>Login :</legend>
                <p>
                    <label for="username">Username</label>
                    <input name="username" type="text" id="username" />
                </p>
                <p>
                    <label for="password">Password</label>
                    <input name="password" type="password" id="password" />
                </p>
          <input type="submit" name="Submit" value="Log in" />
        </fieldset>
    </form>
    <p>Note : you need cookies enabled to log in.<br />
    Don't have an account ? <a href='register.php'>Register</a> now !<br />
    Lost your password ? <a href='#' class='trigger'>Reset</a> it !</p>
    <div class='toggle_container'>
<hr>
    <form name='resetPass' method='post' action='reset-pass.php'>
    <input placeholder='Enter your email address' name='email' type='email' />
    <input type="submit" name="Submit" value="Send new password" />
    </form>
    </div>
</section>
<? require_once("inc/footer.php"); ?>
<!-- BEGIN PASSSWORD RESET FORM -->
<script>
$(document).ready(function(){
	$(".toggle_container").hide();
	$("a.trigger").click(function(){
		$('.toggle_container').slideToggle("slow");
	});
});
</script>

