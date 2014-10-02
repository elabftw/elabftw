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
$_SESSION['prefs']['lang'] = 'fr-FR';
require_once 'lang/'.$_SESSION['prefs']['lang'].'.php';
$page_title = REGISTER_TITLE;
$selected_menu = null;
require_once 'inc/connect.php';
require_once 'inc/functions.php';
require_once 'inc/head.php';
require_once 'inc/info_box.php';
// Check if we're logged in
if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1) {
    display_message('error', REGISTER_LOGOUT);
    require_once 'inc/footer.php';
    exit;
}
?>
<!-- Password complexity visualizer -->
<script src="js/jquery.complexify.js/jquery.complexify.min.js"></script>
<script src="js/jquery.complexify.js/jquery.complexify.banlist.js"></script>

<menu class='border'><a href='login.php'><img src='img/arrow-left-blue.png' alt='' /> <?php echo REGISTER_BACK_TO_LOGIN;?></a></menu>
<section class='center'>
    <!-- Register form -->
    <form id='regform' method="post" class='loginform' autocomplete="off" action="register-exec.php">
        <h2><?php echo REGISTER_H2;?></h2>
        <div style='margin:auto;width:50%'>
        <p class='two-columns'>
        <label class='block' for="team"><?php echo TEAM;?></label>
            <select name='team' id='team' required>
            <option value=''><?php echo REGISTER_DROPLIST;?></option>
                <?php
                $sql = "SELECT team_id, team_name FROM teams ORDER by team_name";
                $req = $pdo->prepare($sql);
                $req->execute();
                while ($teams = $req->fetch()) {
                    echo "<option value = '".$teams['team_id']."'>".$teams['team_name']."</option>";
                }
                ?>
            </select>
            <label class='block' for="username"><?php echo USERNAME;?></label>
            <input name="username" type="text" id="username" required />
            <label class='block' for="email"><?php echo EMAIL;?></label>
            <input name="email" type="email" id="email" required />
            <label class='block' for="firstname"><?php echo FIRSTNAME;?></label>
            <input name="firstname" type="text" id="firstname" required />
            <!-- add two br to fix layout in chrome --><br><br>
            <label class='block' for="lastname"><?php echo LASTNAME;?></label>
            <input name="lastname" type="text" id="lastname" required />
            <label class='block' for="password"><?php echo PASSWORD;?></label>
            <input name="password" type="password" title='8 characters minimum' id="password" pattern=".{8,}" required />
            <label class='block' for="cpassword"><?php echo REGISTER_CONFIRM_PASSWORD;?></label>
            <input name="cpassword" type="password" id="cpassword" pattern=".{8,}" required />
            <label class='block' for='comlexity'><?php echo REGISTER_PASSWORD_COMPLEXITY;?></label>
            <input id="complexity" disabled />
        </p>
    </div>
        <div id='submitDiv'>
        <button type="submit" name="Submit" class='submit button'><?php echo REGISTER_BUTTON;?></button>
        </div>
    </form>
    <!-- end register form -->
</section>

<script>
function validatePassword(){
    var pass=document.getElementById("password").value;
    var cpass=document.getElementById("cpassword").value;
    if (pass != cpass) {
        document.getElementById("cpassword").setCustomValidity("<?php echo PASSWORDS_DONT_MATCH;?>");
    } else {
        //empty string means no validation error
        document.getElementById("cpassword").setCustomValidity(''); 
    }
}

$(document).ready(function() {
    // give focus to the first field on page load
    document.getElementById("team").focus();
    // password complexity
    $("#password").complexify({}, function (valid, complexity){
        if (complexity < 20) {
            $('#complexity').css({'background-color':'red'});
            $('#complexity').css({'color':'white'});
            $('#complexity').val('<?php echo CHANGE_PASS_WEAK;?>');
            $('#complexity').css({'border-color' : '#e3e3e3'});
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
        } else if (complexity < 30) {
            $('#complexity').css({'color':'#white'});
            $('#complexity').css({'background-color':'orange'});
            $('#complexity').val('<?php echo CHANGE_PASS_AVERAGE;?>');
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'border-color' : '#e3e3e3'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
        } else if (complexity < 50) {
            $('#complexity').css({'color':'white'});
            $('#complexity').val('<?php echo CHANGE_PASS_GOOD;?>');
            $('#complexity').css({'background-color':'green'});
            $('#complexity').css({'box-shadow': '0 0  yellow'});
            $('#complexity').css({'-moz-box-shadow': '0 0 yellow'});
            $('#complexity').css({'border-color' : '#e3e3e3'});
        } else if (complexity < 99) {
            $('#complexity').css({'color':'black'});
            $('#complexity').val('<?php echo CHANGE_PASS_STRONG;?>');
            $('#complexity').css({'background-color':'#ffd700'});
            $('#complexity').css({'box-shadow': '0px 0px 15px 5px #ffd700'});
            $('#complexity').css({'border' : 'none'});
            $('#complexity').css({'-moz-box-shadow': '0px 0px 15px 5px #ffd700'});
        } else {
            $('#complexity').css({'color':'#797979'});
            $('#complexity').val('<?php echo CHANGE_PASS_NO_WAY;?>');
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
