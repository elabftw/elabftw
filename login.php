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
session_start();
$page_title = 'Login';
require_once('inc/head.php');
require_once('inc/menu.php');
require_once('inc/info_box.php');
// Check if already logged in
if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1) {
    die('You are already logged in !');
}
// Page begin
echo "<h2>LOGIN</h2>";
?>
<section class='center'>
<form method="post" action="login-exec.php">
<div class='item'>
<p>Username <input name="username" type="text" class="textfield" value='<?php if(isset($_SESSION['username'])){
    echo $_SESSION['username'];
unset($_SESSION['username']);}?>' id="username" /></p>
      <p>Password <input name="password" type="password" class="textfield" id="password" /></p>
      <input type="submit" name="Submit" value="Login" />
</form>
<!-- BEGIN PASSSWORD RESET FORM -->
<script type='text/javascript'>
   function AppearEffect(element){
       new Effect.toggle(element, 'blind', {duration:0.5});
   }
</script>
</script>
<hr>
<span onclick="AppearEffect('toggle_container');"><em>Click here to reset password</em></span>
<div style='display:none' id='toggle_container'>
<br />
<form name='resetPass' method='post' action='reset-pass.php'>
<input placeholder='Enter your email address' name='email' />
<input type="submit" name="Submit" value="Send me a new one !" />
</form>
</div>
</section>
<? require_once("inc/footer.php"); ?>
</body>
</html>
