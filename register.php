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
require_once 'inc/head.php';
$page_title = 'Register';
require_once 'inc/connect.php';
require_once 'inc/menu.php';
require_once 'inc/info_box.php';
require_once 'inc/functions.php';
// Check if we're logged in
if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1) {
    $message = "Please <a style='text-decoration:underline' href='logout.php'>logout</a> before you register another account.";
    display_message('error', $message);
    require_once 'inc/footer.php';
    die();
}
?>
<!-- Password complexity visualizer -->
<script src="bower_components/jquery.complexify.js/jquery.complexify.min.js"></script>
<script src="bower_components/jquery.complexify.js/jquery.complexify.banlist.js"></script>

<section>
    <!-- Register form -->
    <form method="post" autocomplete="off" action="register-exec.php" class='innerinnerdiv'>
        <fieldset>
            <legend>Create your account :</legend>
                <p>
                    <label for="team">Team</label>
                    <select name='team' id='team' required>
                        <option value=''>------- Select a team -------</option>
                        <?php
                        $sql = "SELECT * FROM teams ORDER by team_name";
                        $req = $pdo->prepare($sql);
                        $req->execute();
                        while ($teams = $req->fetch()) {
                            echo "<option value = '".$teams['team_id']."'>".$teams['team_name']."</option>";
                        }
                        ?>
                    </select>
                </p>
                <p>
                    <label for="firstname">Firstname</label>
                    <input name="firstname" type="text" id="firstname" required />
                </p>
                <p>
                    <label for="lastname">Lastname</label>
                    <input name="lastname" type="text" id="lastname" required />
                </p>
                <p>
                    <label for="username">Username</label>
                    <input name="username" type="text" id="username" required />
                </p>
                <p>
                    <label for="email">Email</label>
                    <input name="email" type="email" id="email" required />
                </p>
                <p>
                    <label for="password">Password</label>
                    <input name="password" type="password" title='8 characters minimum' id="password" pattern=".{8,}" required />
                </p>
                <p>
                    <label for="cpassword">Confirm password</label>
                    <input name="cpassword" type="password" id="cpassword" pattern=".{8,}" required />
                </p>
                Password complexity (for your information) : <span id="complexity">0%</span><br /><br />
                <div id='submitDiv'>
                <button type="submit" name="Submit" class='submit button'>Register</button>
                </div>
        </fieldset>
    </form>
    <!-- end register form -->
</section>

<script>
function validatePassword(){
    var pass=document.getElementById("password").value;
    var cpass=document.getElementById("cpassword").value;
    if (pass != cpass) {
        document.getElementById("cpassword").setCustomValidity("Passwords don't match");
    } else {
        //empty string means no validation error
        document.getElementById("cpassword").setCustomValidity(''); 
    }
}

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
			var username = firstname.charAt(0) + lastname;
			this.value = username.toLowerCase();
		}
	});
    // check if both passwords are the same
    document.getElementById("password").onchange = validatePassword;
    document.getElementById("cpassword").onchange = validatePassword;

});
</script>
<?php require_once 'inc/footer.php';
