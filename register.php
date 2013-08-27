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
if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1) {
    die("<ul>
        <li>Please <a href='logout.php'>logout</a> before you register another account.</li>
        </ul>");
}
?>
<!-- Password complexity visualizer -->
<script src="js/jquery.complexify.min.js"></script>
<!-- Form validation client-side -->
<script src="js/parsley.min.js"></script>


<section>
    <!-- Register form -->
    <form name="regForm" data-validate="parsley" id="regForm" method="post" action="register-exec.php" class='innerinnerdiv'>
        <fieldset>
            <legend>Create your account :</legend>
                <p>
                    <label for="firstname">Firstname</label>
                    <input name="firstname" type="text" id="firstname" data-trigger="change" data-required="true" />
                </p>
                <p>
                    <label for="lastname">Lastname</label>
                    <input name="lastname" type="text" id="lastname" data-trigger="change" data-required="true" />
                </p>
                <p>
                    <label for="username">Username</label>
                    <input name="username" type="text" id="username" data-trigger="change" data-required="true" />
                </p>
                <p>
                    <label for="email">Email</label>
                    <input name="email" type="email" id="email" data-trigger="change" data-required="true" data-type="email" />
                </p>
                <p>
                    <label for="password">Password</label>
                    <input name="password" type="password" id="password" data-trigger="change" data-minlength="4" />
                </p>
                <p>
                    <label for="cpassword">Confirm password</label>
                    <input name="cpassword" type="password" id="cpassword" data-trigger="change" data-equalto="#password" data-error-message="The passwords do not match !" />
                </p>
                Password complexity (for your information) : <span id="complexity">0%</span><br /><br />
                <div id='submitDiv'>
                <input type="submit" name="Submit" class='submit' value="Register" />
                </div>
        </fieldset>
    </form>
    <!-- end register form -->
</section>

<style>
.parsley-error {
    color:red;
    background-color:yellow;
}
.parsley-error-list {
    color:red;
    font-weight:bold;
}
</style>

<script>
$(document).ready(function() {
    // give focus to the first field on page load
    document.getElementById("firstname").focus();
    // password complexity
    $("#password").complexify({}, function (valid, complexity){
        if (complexity < 30) {
            $('#complexity').css({'color':'red'});
        } else {
            $('#complexity').css({'color':'green'});
        }
        $("#complexity").html(Math.round(complexity) + '%');
    });
	// propose username by combining firstname's first letter and lastname
	$("#username").focus(function() {
		var firstname = $("#firstname").val();
		var lastname = $("#lastname").val();
		if(firstname && lastname && !this.value) {
			this.value = firstname.charAt(0) + lastname;
		}
	});
});
</script>
<?php require_once('inc/footer.php'); ?>

