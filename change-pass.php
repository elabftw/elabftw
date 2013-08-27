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
$page_title = 'Change your password';
require_once('inc/head.php');
require_once('inc/menu.php');
require_once('inc/info_box.php');
// get the unique key
$key = filter_var($_GET['key'], FILTER_SANITIZE_STRING);
$userid = filter_var($_GET['userid'], FILTER_VALIDATE_INT);
?>
<section class='center'>
    <form method="post" action="reset-exec.php">
    <div class='item'>
    <p>New password <input name="password" type="password" class="textfield" id='passwordtxt' /></p>
    <p>Type it again <input name="cpassword" type="password" class="textfield" id='cpasswordtxt' /></p>
<div id="checkPasswordMatchDiv"><p>Passwords do not match !</p></div>
    <input type="hidden" name="key" value="<?php echo $key;?>" />
    <input type="hidden" name="userid" value="<?php echo $userid;?>" />
    </form>
</section>
<script>
// we check for password match here
function checkPasswordMatch() {
    var password = $("#passwordtxt").val();
    var confirmPassword = $("#cpasswordtxt").val();

    if (password != confirmPassword)
        $("#checkPasswordMatchDiv").html("<p>Passwords do not match !</p>");
    else
        $("#checkPasswordMatchDiv").html("<input type='submit' name='Submit' value='Change password' />");
}

$(document).ready(function () {
   $("#cpasswordtxt").keyup(checkPasswordMatch);
});
</script>
<? require_once("inc/footer.php"); ?>

