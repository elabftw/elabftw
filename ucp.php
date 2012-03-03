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
require_once('inc/auth.php');
$page_title = 'User Control Panel';
require_once('inc/head.php');
require_once('inc/menu.php');
require_once('inc/connect.php');
require_once('inc/info_box.php');

echo '<h2>USER CONTROL PANEL</h2>';

// SQL for UCP
$sql = "SELECT username, email, firstname, lastname FROM users WHERE userid = ".$_SESSION['userid'];
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// BEGIN UCP PAGE
?>
<div class='item'>
<h3>PERSONNAL INFORMATIONS</h3>
<div class='innerdiv'>
<form name="profileForm" method="post" action="ucp-exec.php">
<input type='hidden' name='main'>
      <p>Enter your current password to change personnal infos <input id='currpass' name="currpass" type="password" /></div>
<br />
<div class='innerinnerdiv'>
      New password <input name="newpass" type="password" /><br />
      Confirm new password <input name="cnewpass" type="password" /><br />
      Change Email <input name="email" value='<?php echo $data['email'];?>' cols='20' rows='1' /><br />
      Username <input name="username" value='<?php echo $data['username'];?>' cols='20' rows='1' /><br />
      Firstname <input name="firstname" value='<?php echo $data['firstname'];?>' cols='20' rows='1' /><br />
      Lastname <input name="lastname" value='<?php echo $data['lastname'];?>' cols='20' rows='1' /><br /></p>
<!-- SUBMIT BUTTON -->
<div id='submitDiv'><input type="submit" name="Submit" class='submitbutton' value="Update profile" /></div>
</form>
</div>
</div>

<div class='item'>
<h3>DISPLAY PREFERENCES</h3>
<form action='ucp-exec.php' method='post'>
<h4>View mode :</h4>
<select name='display'><option value='default'>default</option>
<option value='compact' <?php echo ($_SESSION['prefs']['display'] === 'compact') ? "selected" : "";?>>compact</option>
</select>
<br /><br />
<h4>Order by :</h4>
<select name="order">
<option
<?php
if ($_SESSION['prefs']['order'] === 'date'){
    echo ' selected ';}?>value="date">Date</option>
<option
<?php
if ($_SESSION['prefs']['order'] === 'id'){
    echo ' selected ';}?>value="id">Item ID</option>
<option
<?php
if ($_SESSION['prefs']['order'] === 'title'){
    echo ' selected ';}?>value="title">Title</option>
</select>

<select name="sort">
<option
<?php
if ($_SESSION['prefs']['sort'] === 'desc'){
    echo ' selected ';}?>value="desc">Newer first</option>
<option
<?php
if ($_SESSION['prefs']['sort'] === 'asc'){
    echo ' selected ';}?>value="asc">Older first</option>
</select><br />
<br />
<h4>Number of items to show on each page :</h4>
<input type='text' size='2' maxlength='2' value='<?php echo $_SESSION['prefs']['limit'];?>' name='limit'>
<br />
<br />
<h4>Theme (hover to preview) :</h4><br />
<script type='text/javascript'>
function setTmpTheme(theme){
    document.getElementById('maincss').href = 'themes/'+theme+'/style.css';
}
</script>
<div class='center'>
<input type='radio' name='theme' value='default' <?php if ($_SESSION['prefs']['theme'] === 'default'){ echo "checked='checked'";}?>>Default<br />
<img onmouseover="setTmpTheme('default');" onmouseout="setTmpTheme('<?php echo $_SESSION['prefs']['theme'];?>')" src='themes/default/img/sample.png' alt='default theme'>
</div>
<br />
<div class='center'>
<input type='radio' name='theme' value='l33t' <?php if ($_SESSION['prefs']['theme'] === 'l33t'){ echo "checked='checked'";}?>>l33t<br />
<img onmouseover="setTmpTheme('l33t');" onmouseout="setTmpTheme('<?php echo $_SESSION['prefs']['theme'];?>')" src='themes/l33t/img/sample.png' alt='l33t theme'>
</div>
<br /><br />
<!-- SUBMIT BUTTON -->
<div id='submitDiv'><input type="submit" name="Submit" class='submitbutton' value="Set preferences" /></div>
</form>
</div>

<section class='item'>
<h3>KEYBOARD SHORTCUTS</h3>
<br />
<form action='ucp-exec.php' method='post'>
<input type='hidden' name='shortcuts'>
<span class='simple_border'>Create item : <input type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['create'];?>' name='create'></span>
<span class='simple_border'>Edit item : <input type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['edit'];?>' name='edit'></span>
<span class='simple_border'>Submit : <input type='text' size='1' maxlength='1' value='<?php echo $_SESSION['prefs']['shortcuts']['submit'];?>' name='submit'></span>
<!-- SUBMIT BUTTON -->
<div id='submitDiv'><input type="submit" name="Submit" class='submitbutton' value="Change shortcuts" /></div>
</form>
</section>

<section class='item'>
<h3>EXPORT DATA</h3>
<p>This will put all of your experiments + files in a .zip archive.</p>
<form action='ucp-exec.php' method='post'>
<input name='export' type='submit' value='DO IT'>
</form>
</section>


<?php
require_once("inc/footer.php");
// Give focus to password field
echo "<script type='text/javascript'>document.getElementById('currpass').focus();</script>";
?>
