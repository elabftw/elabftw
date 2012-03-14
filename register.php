<?php
/******************************************************************************
*   Copyright 2012 Nicolas CARPi
*   This file is part of eLabFTW. 
*
*    eLabFTW is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    eLabFTW is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.
*
********************************************************************************/
session_start();
require_once('inc/head.php');
require_once('inc/menu.php');
require_once('inc/info_box.php');
?>
<h3>REGISTER</h3>

<!-- Register form -->
<section>
<div class='item'>
<div id='innerdiv'>
<form name="regForm" method="post" action="register-exec.php" class='innerinnerdiv'>
      <p>Username <input name="username" type="text" class="textfield" id="username" /><br />
      Firstname <input name="firstname" type="text" class="textfield" id="firstname" /><br />
      Lastname <input name="lastname" type="text" class="textfield" id="lastname" /><br />
      Email <input name="email" type="text" class="textfield" id="email" /><br />
      Password <input name="password" type="password" class="textfield" id="password" /><br />
      Confirm Password <input name="cpassword" type="password" class="textfield" id="cpassword" /><br /><br />
<div id='submitDiv'>
<!-- js on submit button to avoid double requests -->
      <input type="submit" name="Submit" class='submit' value="Register" onclick="this.disabled = 1;" />
</div>
</div>
</form>
<!-- end register form -->
<!-- give focus to username field on page load -->
<script type="text/javascript">
document.getElementById("username").focus();
</script>
</section>
<? require_once('inc/footer.php'); ?>

