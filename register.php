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
$page_title='Register';
require_once('inc/menu.php');
require_once('inc/info_box.php');
// Check if we're logged in
if ($_SESSION['auth'] == 1) {
    die("<ul>
        <li>Please <a href='logout.php'>logout</a> before you register another account.</li>
        </ul>");
}
?>
<!-- Password complexity visualizer -->
<script src="js/jquery.complexify.min.js"></script>


<section>
    <div class='item'>
        <div id='innerdiv'>
        <!-- Register form -->
            <form name="regForm" method="post" action="register-exec.php" class='innerinnerdiv'>
                  <p>Username <input name="username" type="text" id="username" /><br />
                  Firstname <input name="firstname" type="text" id="firstname" /><br />
                  Lastname <input name="lastname" type="text" id="lastname" /><br />
                  Email <input name="email" type="text"  id="email" /><br />
                  Password <input name="password" type="password" id="password" /><br />
                  Confirm Password <input name="cpassword" type="password" id="cpassword" /><br />
                  Password complexity : <span id="complexity">0%</span><br />
            <div id='submitDiv'>
                <input type="submit" name="Submit" class='submit' value="Register" />
            </div>
            </form>
        </div>
    <!-- end register form -->
    </div>
</section>
<script>
$(document).ready(function() {
    // give focus to username field on page load
    document.getElementById("username").focus();
    // password complexity
    $("#password").complexify({}, function (valid, complexity){
        if (complexity < 30) {
            $('#complexity').css({'color':'red'});
        } else {
            $('#complexity').css({'color':'green'});
        }
        $("#complexity").html(Math.round(complexity) + '%');
    });
});
</script>
<?php require_once('inc/footer.php'); ?>
