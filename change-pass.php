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
// TODO load default lang
require_once 'lang/en-GB.php';
$page_title = CHANGE_PASS_TITLE;
require_once 'inc/functions.php';
require_once 'inc/head.php';
require_once 'inc/info_box.php';
// get the unique key
if (isset($_GET['key']) && strlen($_GET['key']) === 64 && isset($_GET['userid'])) {
    $key = filter_var($_GET['key'], FILTER_SANITIZE_STRING);
    $userid = filter_var($_GET['userid'], FILTER_VALIDATE_INT);
} else {
    header('Location:login.php');
    exit;
}
?>

<section class='center'>
    <form method="post" class='loginform' action="reset-exec.php">
        <p>
            <label class='block' for='passwordtxt'><?php echo CHANGE_PASS_PASSWORD;?></label>
            <input name="password" type="password" title='<?php echo CHANGE_PASS_HELP;?>' id="password" pattern=".{8,}" required />
            <label class='block' for='cpasswordtxt'><?php echo CHANGE_PASS_REPEAT_PASSWORD;?></label>
            <input name="cpassword" type="password" title='<?php echo CHANGE_PASS_HELP;?>' id="cpassword" pattern=".{8,}" required />
            <label class='block' for='complexity'><?php echo CHANGE_PASS_COMPLEXITY;?></label>
            <input id='complexity' disabled />

            <div id="checkPasswordMatchDiv"><p><?php echo PASSWORDS_DONT_MATCH;?></p></div>
            <input type="hidden" name="key" value="<?php echo $key;?>" />
            <input type="hidden" name="userid" value="<?php echo $userid;?>" />
        </p>
    </form>
</section>

<!-- Password complexity visualizer -->
<script src="js/jquery.complexify.js/jquery.complexify.min.js"></script>
<script src="js/jquery.complexify.js/jquery.complexify.banlist.js"></script>
<script>
// we check for password match here
function checkPasswordMatch() {
    var password = $("#password").val();
    var confirmPassword = $("#cpassword").val();

    if (password != confirmPassword)
        $("#checkPasswordMatchDiv").html("<p><?php echo PASSWORDS_DONT_MATCH;?></p>");
    else
        $("#checkPasswordMatchDiv").html("<button class='button' type='submit' name='Submit'><?php echo CHANGE_PASS_BUTTON;?></button>");
}

$(document).ready(function () {
   $("#cpassword").keyup(checkPasswordMatch);
    // give focus to the first field on page load
    document.getElementById("password").focus();
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
});
</script>
<?php
require_once 'inc/footer.php';
