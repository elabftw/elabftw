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
$page_title = 'Register';
require_once 'inc/connect.php';
require_once 'inc/functions.php';
require_once 'inc/head.php';
require_once 'inc/info_box.php';
// Check if we're logged in
if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1) {
    $message = "Please <a style='text-decoration:underline' href='logout.php'>logout</a> before you register another account.";
    display_message('error', $message);
    require_once 'inc/footer.php';
    die();
}
?>
<!-- Password complexity visualizer -->
<script src="js/jquery.complexify.js/jquery.complexify.min.js"></script>
<script src="js/jquery.complexify.js/jquery.complexify.banlist.js"></script>

<span class='backdiv'><a href='login.php'><img src='img/arrow-left-blue.png' alt='' /> back to login page</a></span>
<section class='center'>
    <!-- Register form -->
    <form id='regform' method="post" class='loginform' autocomplete="off" action="register-exec.php">
        <h2>Create your account</h2>
        <div style='margin:auto;width:50%'>
        <p class='two-columns'>
            <label for="team">Team</label>
            <select name='team' id='team' required>
                <option value=''>------- Select a team -------</option>
                <?php
                $sql = "SELECT team_id, team_name FROM teams ORDER by team_name";
                $req = $pdo->prepare($sql);
                $req->execute();
                while ($teams = $req->fetch()) {
                    echo "<option value = '".$teams['team_id']."'>".$teams['team_name']."</option>";
                }
                ?>
            </select>
            <label for="username">Username</label>
            <input name="username" type="text" id="username" required />
            <label for="email">Email</label>
            <input name="email" type="email" id="email" required />
            <label for="firstname">Firstname</label>
            <input name="firstname" type="text" id="firstname" required />
            <label for="lastname">Lastname</label>
            <input name="lastname" type="text" id="lastname" required />
            <label for="password">Password</label>
            <input name="password" type="password" title='8 characters minimum' id="password" pattern=".{8,}" required />
            <label for="cpassword">Confirm password</label>
            <input name="cpassword" type="password" id="cpassword" pattern=".{8,}" required />
            <label for='comlexity'>Password complexity</label>
            <input id="complexity" disabled />
        </p>
    </div>
        <div id='submitDiv'>
        <button type="submit" name="Submit" class='submit button'>create</button>
        </div>
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
        if (complexity < 20) {
            $('#complexity').css({'background-color':'red'});
            $('#complexity').css({'color':'white'});
            $('#complexity').val('Weak password');
            $('#complexity').css({'border-color' : '#e3e3e3'});
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
        } else if (complexity < 30) {
            $('#complexity').css({'color':'#white'});
            $('#complexity').css({'background-color':'orange'});
            $('#complexity').val('Average password');
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'border-color' : '#e3e3e3'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
        } else if (complexity < 50) {
            $('#complexity').css({'color':'white'});
            $('#complexity').val('Good password');
            $('#complexity').css({'background-color':'green'});
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
            $('#complexity').css({'border-color' : '#e3e3e3'});
        } else if (complexity < 99) {
            $('#complexity').css({'color':'black'});
            $('#complexity').val('Strong password');
            $('#complexity').css({'background-color':'#ffd700'});
            $('#complexity').css({'box-shadow': '0px 0px 15px 5px #ffd700'});
            $('#complexity').css({'border' : 'none'});
            $('#complexity').css({'-moz-box-shadow': '0px 0px 15px 5px #ffd700'});
        } else {
            $('#complexity').css({'color':'#797979'});
            $('#complexity').val('I don\'t believe you');
            $('#complexity').css({'background-color':'#e3e3e3'});
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
            $('#complexity').css({'border-color' : '#e3e3e3'});
        }

        //$("#complexity").html(Math.round(complexity) + '%');
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
